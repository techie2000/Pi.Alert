#!/bin/bash
# ------------------------------------------------------------------------------
#  Pi.Alert
#  Open Source Network Guard / WIFI & LAN intrusion detector 
#
#  pialert_install.sh - Installation script
# ------------------------------------------------------------------------------
#  Puche 2021        pi.alert.application@gmail.com        GNU GPLv3
#  leiweibau 2024+                                          GNU GPLv3
# ------------------------------------------------------------------------------

# ------------------------------------------------------------------------------
# Variables
# ------------------------------------------------------------------------------
  COLS=70
  ROWS=12

  INSTALL_DIR=~
  PIALERT_HOME="$INSTALL_DIR/pialert"

  LIGHTTPD_CONF_DIR="/etc/lighttpd"
  WEBROOT="/var/www/html"
  PIALERT_DEFAULT_PAGE=false

  LOG="pialert_install_`date +"%Y-%m-%d_%H-%M"`.log"

  # MAIN_IP=`ip -o route get 1 | sed -n 's/.*src \([0-9.]\+\).*/\1/p'`
  MAIN_IP=`ip -o route get 1 | sed 's/^.*src \([^ ]*\).*$/\1/;q'`

  PIHOLESIX_CHECK=false
  PIHOLESIX_CONFIG=false

  USE_PYTHON_VERSION=0
  PYTHON_BIN=python

  FIRST_SCAN_KNOWN=true

  DDNS_ACTIVE=False
  DDNS_DOMAIN='your_domain.freeddns.org'
  DDNS_USER='dynu_user'
  DDNS_PASSWORD='A0000000B0000000C0000000D0000000'
  DDNS_UPDATE_URL='https://api.dynu.com/nic/update?'


# ------------------------------------------------------------------------------
# Main
# ------------------------------------------------------------------------------
main() {
  print_superheader "Pi.Alert Installation"
  log "`date`"
  log "Logfile: $LOG"
  install_dependencies

  check_pihole
  check_pialert_home
  ask_config

  set -e

  install_lighttpd
  install_arpscan
  install_python
  install_pialert

  print_header "Installation process finished"
  print_msg "Use: - http://pi.alert/"
  print_msg "     - http://$MAIN_IP/pialert/"
  print_msg "To access Pi.Alert web"
  print_msg ""

  move_logfile
}

# ------------------------------------------------------------------------------
# Check Pihole
# ------------------------------------------------------------------------------

check_pihole() {

    if systemctl is-active --quiet pihole-FTL; then
        # Pi-hole Version abrufen
        VERSION_OUTPUT=$(sudo pihole -v)

        # Extrahiere die Hauptversionsnummer
        CORE_VERSION=$(echo "$VERSION_OUTPUT" | grep -oP 'Core version is v\K[0-9]+')

        if [[ $CORE_VERSION -ge 6 ]]; then
            PIHOLESIX_CHECK=true
        else
            PIHOLESIX_CHECK=false
        fi
    else
        PIHOLESIX_CHECK=false
    fi
}

# ------------------------------------------------------------------------------
# Ask config questions
# ------------------------------------------------------------------------------
ask_config() {
  # Ask installation
  ask_yesno "This script will install Pi.Alert in this system using this path:\n$PIALERT_HOME" \
            "Do you want to continue ?"
  if ! $ANSWER ; then
    exit 1
  fi

  # Ask Pihole detection
  if $PIHOLESIX_CHECK; then
    ask_yesno "A Pi-hole 6 installation was detected." \
              "The Pi-hole web interface is changed to port 8080 to avoid conflicts with this installation. If you select NO, the Pi.Alert installation will be aborted." "YES"
    if $ANSWER ; then
      PIHOLESIX_CONFIG=true
    else
      exit 1
    fi
  fi

  # Ask Pi.Alert deafault page
  PIALERT_DEFAULT_PAGE=false
  if ! $PIHOLE_ACTIVE && ! $PIHOLE_INSTALL; then
    ask_yesno "As Pi-hole is not going to be available in this system," \
              "Do you want to use Pi.Alert as default web server page ?" "YES"
    if $ANSWER ; then
      PIALERT_DEFAULT_PAGE=true
    fi
  fi

  # Ask Python version
  ask_option "Is Python 3 already installed in the system ?" \
              2 \
              0 " - Yes it is (DEFAULT)" \
              3 " - Install Python 3"
  if [ "$ANSWER" = "" ] ; then
    USE_PYTHON_VERSION=0
  else
    USE_PYTHON_VERSION=$ANSWER
  fi

  # Ask first scan options
  ask_yesno "First Scan options" \
            "Do you want to mark the new devices as known devices during the first scan?" "YES"
  FIRST_SCAN_KNOWN=$ANSWER

  # Ask Dynamic DNS config
  DDNS_ACTIVE=false
  ask_yesno "Pi.Alert can update your Dynamic DNS IP (i.e with www.dynu.net)" \
            "Do you want to activate this feature ?"
  if $ANSWER ; then
    ask_yesno "Dynamics DNS updater needs a DNS with IP Update Protocol" \
              "(i.e with www.dynu.net). Do you want to continue ?"
    DDNS_ACTIVE=$ANSWER
  fi

  if $DDNS_ACTIVE ; then
    ask_input "" "Domain to update:" "your_domain.freeddns.org"
    DDNS_DOMAIN=$ANSWER

    ask_input "" "DDNS user:" "dynu_user"
    DDNS_USER=$ANSWER

    ask_input "" "DDNS password:" "A0000000B0000000C0000000D0000000"
    DDNS_PASSWORD=$ANSWER

    ask_input "" "URL to update DDNS IP:" "https://api.dynu.com/nic/update?"
    DDNS_UPDATE_URL=$ANSWER
  fi
  
  # Final config message
  msgbox "Configuration finished. To update the configuration, edit file:" \
         "$PIALERT_HOME/config/pialert.conf"

  msgbox "" "The installation will start now"
}

# ------------------------------------------------------------------------------
# Install Lighttpd & PHP
# ------------------------------------------------------------------------------
install_lighttpd() {

  if $PIHOLESIX_CONFIG ; then
    echo "Pi-hole detected. Webinterface moved to Port 8080..."
    sudo pihole-FTL --config webserver.port 8080o,443so,[::]:8080o,[::]:443so
    sudo systemctl restart pihole-FTL
    echo "Pi-hole Configuration applied"
  fi

  print_header "Lighttpd & PHP"

  print_msg "- Installing apt-utils..."
  sudo apt-get install apt-utils -y                                         2>&1 >> "$LOG"

  print_msg "- Installing lighttpd..."
  sudo apt-get install lighttpd -y                                          2>&1 >> "$LOG"
  
  print_msg "- Installing PHP..."
  sudo apt-get install php php-cgi php-fpm php-curl php-sqlite3 php-xml -y          2>&1 >> "$LOG"

  print_msg "- Activating PHP..."
  ERRNO=0
  sudo lighttpd-enable-mod fastcgi-php 2>&1                 >>"$LOG" || ERRNO=$? 
  log_no_screen "-- Command error code: $ERRNO"
  if [ "$ERRNO" = "1" ] ; then
    process_error "Error activating PHP"
  fi
  
  print_msg "- Restarting lighttpd..."
  sudo service lighttpd restart                                             2>&1 >> "$LOG"
  # sudo /etc/init.d/lighttpd restart                             2>&1 >> "$LOG"

  print_msg "- Installing sqlite3..."
  sudo apt-get install sqlite3 -y                                           2>&1 >> "$LOG"

  print_msg "- Installing mmdblookup"
  sudo apt-get install mmdb-bin -y                                          2>&1 >> "$LOG"
}

# ------------------------------------------------------------------------------
# Install arp-scan & dnsutils
# ------------------------------------------------------------------------------
install_arpscan() {
  print_header "arp-scan, dnsutils and nmap"

  print_msg "- Installing arp-scan..."
  sudo apt-get install arp-scan -y                                          2>&1 >> "$LOG"
  sudo mkdir -p /usr/share/ieee-data                                        2>&1 >> "$LOG"

  print_msg "- Testing arp-scan..."
  sudo arp-scan -l | head -n -3 | tail +3 | tee -a "$LOG"

  print_msg "- Installing dnsutils & net-tools..."
  sudo apt-get install dnsutils curl net-tools libwww-perl libtext-csv-perl -y   2>&1 >> "$LOG"

  print_msg "- Installation of tools for hostname detection..."
  sudo apt-get install avahi-utils nbtscan -y                               2>&1 >> "$LOG"

  print_msg "- Installing nmap, zip, aria2 and wakeonlan"
  sudo apt-get install nmap zip wakeonlan aria2 fping -y                          2>&1 >> "$LOG"
}
  
# ------------------------------------------------------------------------------
# Install Python
# ------------------------------------------------------------------------------
install_python() {
  print_header "Python"

  check_python_versions

  if [ $USE_PYTHON_VERSION -eq 0 ] ; then
    print_msg "- Using the available Python version installed"
    if $PYTHON3 ; then
      print_msg "  - Python 3 is available"
      USE_PYTHON_VERSION=3
    elif $PYTHON2 ; then
      print_msg "  - Python 2 is available but no longer compatible with Pi.Alert"
      print_msg "    - Python 3 will be installed"
      USE_PYTHON_VERSION=3
    else
      print_msg "  - Python is not available in this system"
      print_msg "    - Python 3 will be installed"
      USE_PYTHON_VERSION=3
    fi
    echo ""
  fi

  if [ $USE_PYTHON_VERSION -eq 3 ] ; then
    if $PYTHON3 ; then
      print_msg "- Using Python 3"
      sudo apt-get install python3-pip python3-cryptography python3-requests python3-tz python3-tzlocal python3-aiohttp -y                 2>&1 >> "$LOG"
    else
      print_msg "- Installing Python 3..."
      sudo apt-get install python3 python3-pip python3-cryptography python3-requests python3-tz python3-tzlocal python3-aiohttp -y         2>&1 >> "$LOG"
    fi
    print_msg "    - Install additional packages"
    if [ -f /usr/lib/python3.*/EXTERNALLY-MANAGED ]; then
      pip3 -q install mac-vendor-lookup --break-system-packages --no-warn-script-location       2>&1 >> "$LOG"
      pip3 -q install fritzconnection --break-system-packages --no-warn-script-location         2>&1 >> "$LOG"
      pip3 -q install routeros_api --break-system-packages --no-warn-script-location            2>&1 >> "$LOG"
      pip3 -q install pyunifi --break-system-packages --no-warn-script-location                 2>&1 >> "$LOG"
      pip3 -q install openwrt-luci-rpc --break-system-packages --no-warn-script-location        2>&1 >> "$LOG"
      pip3 -q install asusrouter --break-system-packages --no-warn-script-location              2>&1 >> "$LOG"
      pip3 -q install paho-mqtt --break-system-packages --no-warn-script-location               2>&1 >> "$LOG"
    else
      pip3 -q install mac-vendor-lookup  --no-warn-script-location                              2>&1 >> "$LOG"
      pip3 -q install fritzconnection --no-warn-script-location                                 2>&1 >> "$LOG"
      pip3 -q install routeros_api --no-warn-script-location                                    2>&1 >> "$LOG"
      pip3 -q install pyunifi --no-warn-script-location                                         2>&1 >> "$LOG"
      pip3 -q install openwrt-luci-rpc --no-warn-script-location                                2>&1 >> "$LOG"
      pip3 -q install asusrouter --no-warn-script-location                                      2>&1 >> "$LOG"
      pip3 -q install paho-mqtt --no-warn-script-location                                       2>&1 >> "$LOG"
    fi

    print_msg "    - Update 'requests' package to 2.31.0"
    if [ -f /usr/lib/python3.*/EXTERNALLY-MANAGED ]; then
      pip3 -q install "requests>=2.31.0" --break-system-packages --no-warn-script-location       2>&1 >> "$LOG"
    else
      pip3 -q install "requests>=2.31.0" --no-warn-script-location                               2>&1 >> "$LOG"
    fi

    PYTHON_BIN="python3"
  else
    process_error "Unknown Python version to use: $USE_PYTHON_VERSION"
  fi
}

# ------------------------------------------------------------------------------
# Check Python versions available
# ------------------------------------------------------------------------------
check_python_versions() {
  print_msg "- Checking Python 2..."
  if [ -f /usr/bin/python ] ; then
    print_msg "  - Python 2 is installed"
    print_msg "    - `python -V 2>&1`"
    PYTHON2=true
  else
    print_msg "  - Python 2 is NOT installed"
    PYTHON2=false
  fi
  echo ""

  print_msg "- Checking Python 3..."
  if [ -f /usr/bin/python3 ] ; then
    print_msg "  - Python 3 is installed"
    print_msg "    - `python3 -V 2>&1`"
    PYTHON3=true
  else
    print_msg "  - Python 3 is NOT installed"
    PYTHON3=false
  fi
  echo ""
}

# ------------------------------------------------------------------------------
# Install Pi.Alert
# ------------------------------------------------------------------------------
install_pialert() {
  print_header "Pi.Alert"

  download_pialert
  configure_pialert
  test_pialert
  add_jobs_to_crontab
  publish_pialert
  set_pialert_default_page
}

# ------------------------------------------------------------------------------
# Download and uncompress Pi.Alert
# ------------------------------------------------------------------------------
download_pialert() {
  if [ -f "$INSTALL_DIR/pialert_latest.tar" ] ; then
    print_msg "- Deleting previous downloaded tar file"
    rm -r "$INSTALL_DIR/pialert_latest.tar"
  fi
  
  print_msg "- Downloading installation tar file..."
  URL="https://github.com/leiweibau/Pi.Alert/raw/main/tar/pialert_latest.tar"
  # Testing
  # ----------------------------------
  #URL=""
  wget -q --show-progress -O "$INSTALL_DIR/pialert_latest.tar" "$URL"
  echo ""

  print_msg "- Uncompressing tar file"
  tar xf "$INSTALL_DIR/pialert_latest.tar" -C "$INSTALL_DIR" --checkpoint=100 --checkpoint-action="ttyout=."        2>&1 >> "$LOG"
  echo ""

  print_msg "- Deleting downloaded tar file..."
  rm -r "$INSTALL_DIR/pialert_latest.tar"                                                                           2>&1 >> "$LOG"

  print_msg "- Generate autocomplete file..."
  PIALERT_CLI_PATH=$(dirname $PIALERT_HOME)
  sed -i "s|<YOUR_PIALERT_PATH>|$PIALERT_CLI_PATH/pialert|" $PIALERT_HOME/install/pialert-cli.autocomplete

  print_msg "- Copy autocomplete file..."
  if [ -d "/etc/bash_completion.d" ] ; then
      sudo cp $PIALERT_HOME/install/pialert-cli.autocomplete /etc/bash_completion.d/pialert-cli                     2>&1 >> "$LOG"
  elif [ -d "/usr/share/bash-completion/completions" ] ; then
      sudo cp $PIALERT_HOME/install/pialert-cli.autocomplete /usr/share/bash-completion/completions/pialert-cli     2>&1 >> "$LOG"
  fi

}

# ------------------------------------------------------------------------------
# Configure Pi.Alert parameters
# ------------------------------------------------------------------------------
configure_pialert() {
  print_msg "- Setting Pi.Alert config file"

  set_pialert_parameter PIALERT_PATH    "'$PIALERT_HOME'"
  
  set_pialert_parameter DDNS_ACTIVE     "$DDNS_ACTIVE"
  set_pialert_parameter DDNS_DOMAIN     "'$DDNS_DOMAIN'"
  set_pialert_parameter DDNS_USER       "'$DDNS_USER'"
  set_pialert_parameter DDNS_PASSWORD   "'$DDNS_PASSWORD'"
  set_pialert_parameter DDNS_UPDATE_URL "'$DDNS_UPDATE_URL'"
}

# ------------------------------------------------------------------------------
# Set Pi.Alert parameter
# ------------------------------------------------------------------------------
set_pialert_parameter() {
  if [ "$2" = "false" ] ; then
    VALUE="False"
  elif [ "$2" = "true" ] ; then
    VALUE="True"
  else
    VALUE="$2"
  fi
  
  sed -i "/^$1.*=/s|=.*|= $VALUE|" $PIALERT_HOME/config/pialert.conf                             2>&1 >> "$LOG"
}

# ------------------------------------------------------------------------------
# Test Pi.Alert
# ------------------------------------------------------------------------------
test_pialert() {
  print_msg "- Testing Pi.Alert HW vendors database update process..."
  print_msg "- Prepare directories..."
  if [ ! -e /var/lib/ieee-data ]; then
    sudo ln -s /usr/share/ieee-data/ /var/lib/ieee-data                                          2>&1 >> "$LOG"
  fi

  print_msg "*** PLEASE WAIT A COUPLE OF MINUTES..."
  stdbuf -i0 -o0 -e0  $PYTHON_BIN $PIALERT_HOME/back/pialert.py update_vendors_silent            2>&1 | tee -ai "$LOG"

  echo ""
  print_msg "- Testing Pi.Alert Internet IP Lookup..."
  stdbuf -i0 -o0 -e0  $PYTHON_BIN $PIALERT_HOME/back/pialert.py internet_IP                      2>&1 | tee -ai "$LOG"

  echo ""
  print_msg "- Testing Pi.Alert Network scan..."
  print_msg "*** PLEASE WAIT A COUPLE OF MINUTES..."
  stdbuf -i0 -o0 -e0  $PYTHON_BIN $PIALERT_HOME/back/pialert.py 1                                2>&1 | tee -ai "$LOG"

  echo ""
  print_msg "- Enable optional Speedtest..."
  chmod +x $PIALERT_HOME/back/speedtest-cli                                                      2>&1 | tee -ai "$LOG"

  echo ""
  print_msg "- Enable optional pialert-cli..."
  chmod +x $PIALERT_HOME/back/pialert-cli                                                        2>&1 | tee -ai "$LOG"

  if $FIRST_SCAN_KNOWN ; then
    echo ""
    print_msg "- Set devices as Known devices..."
    sqlite3 $PIALERT_HOME/db/pialert.db "UPDATE Devices SET dev_NewDevice=0, dev_AlertEvents=0 WHERE dev_NewDevice=1" 2>&1 >> "$LOG"
  fi
}

# ------------------------------------------------------------------------------
# Add Pi.Alert jobs to crontab
# ------------------------------------------------------------------------------
add_jobs_to_crontab() {
  if crontab -l 2>/dev/null | grep -Fq pialert ; then
    print_msg "- Pi.Alert crontab jobs already exists. This is your crontab:"
    crontab -l | grep -F pialert                                                                 2>&1 | tee -ai "$LOG"
    return    
  fi

  print_msg "- Adding jobs to the crontab..."
  # if [ $USE_PYTHON_VERSION -eq 3 ] ; then
  #   sed -i "s/\<python\>/$PYTHON_BIN/g" $PIALERT_HOME/install/pialert.cron
  # fi

  (crontab -l 2>/dev/null || : ; cat $PIALERT_HOME/install/pialert.cron) | crontab -
}

# ------------------------------------------------------------------------------
# Publish Pi.Alert web
# ------------------------------------------------------------------------------
publish_pialert() {
  if [ -e "$WEBROOT/pialert" ] || [ -L "$WEBROOT/pialert" ] ; then
    print_msg "- Deleting previous Pi.Alert site"
    sudo rm -r "$WEBROOT/pialert"                                                                               2>&1 >> "$LOG"
  fi

  print_msg "- Setting permissions..."
  chmod go+x $INSTALL_DIR
  sudo chgrp -R www-data "$PIALERT_HOME/db"                                                                     2>&1 >> "$LOG"
  sudo chmod -R 775 "$PIALERT_HOME/db"                                                                          2>&1 >> "$LOG"
  sudo chmod -R 775 "$PIALERT_HOME/db/temp"                                                                     2>&1 >> "$LOG"
  sudo chgrp -R www-data "$PIALERT_HOME/config"                                                                 2>&1 >> "$LOG"
  sudo chmod -R 775 "$PIALERT_HOME/config"                                                                      2>&1 >> "$LOG"
  sudo chgrp -R www-data "$PIALERT_HOME/front/reports"                                                          2>&1 >> "$LOG"
  sudo chmod -R 775 "$PIALERT_HOME/front/reports"                                                               2>&1 >> "$LOG"
  sudo chgrp -R www-data "$PIALERT_HOME/front/satellites"                                                       2>&1 >> "$LOG"
  sudo chmod -R 775 "$PIALERT_HOME/front/satellites"                                                            2>&1 >> "$LOG"
  sudo chgrp -R www-data "$PIALERT_HOME/back/speedtest/"                                                        2>&1 >> "$LOG"
  sudo chmod -R 775 "$PIALERT_HOME/back/speedtest/"                                                             2>&1 >> "$LOG"
  chmod +x "$PIALERT_HOME/back/shoutrrr/arm64/shoutrrr"                                                         2>&1 >> "$LOG"
  chmod +x "$PIALERT_HOME/back/shoutrrr/armhf/shoutrrr"                                                         2>&1 >> "$LOG"
  chmod +x "$PIALERT_HOME/back/shoutrrr/x86/shoutrrr"                                                           2>&1 >> "$LOG"
  print_msg "- Create Logfile Symlinks..."
  touch "$PIALERT_HOME/log/pialert.vendors.log"                                                                 2>&1 >> "$LOG"
  touch "$PIALERT_HOME/log/pialert.1.log"                                                                       2>&1 >> "$LOG"
  touch "$PIALERT_HOME/log/pialert.cleanup.log"                                                                 2>&1 >> "$LOG"
  touch "$PIALERT_HOME/log/pialert.webservices.log"                                                             2>&1 >> "$LOG"
  ln -s "$PIALERT_HOME/log/pialert.vendors.log" "$PIALERT_HOME/front/php/server/pialert.vendors.log"            2>&1 >> "$LOG"
  ln -s "$PIALERT_HOME/log/pialert.IP.log" "$PIALERT_HOME/front/php/server/pialert.IP.log"                      2>&1 >> "$LOG"
  ln -s "$PIALERT_HOME/log/pialert.1.log" "$PIALERT_HOME/front/php/server/pialert.1.log"                        2>&1 >> "$LOG"
  ln -s "$PIALERT_HOME/log/pialert.cleanup.log" "$PIALERT_HOME/front/php/server/pialert.cleanup.log"            2>&1 >> "$LOG"
  ln -s "$PIALERT_HOME/log/pialert.webservices.log" "$PIALERT_HOME/front/php/server/pialert.webservices.log"    2>&1 >> "$LOG"

  print_msg "- Set sudoers..."
  sudo $PIALERT_HOME/back/pialert-cli set_sudoers                                                               2>&1 >> "$LOG"

  print_msg "- Publishing Pi.Alert web..."
  sudo ln -s "$PIALERT_HOME/front" "$WEBROOT/pialert"                                                           2>&1 >> "$LOG"

  print_msg "- Configuring http://pi.alert/ redirection..."
  if [ -e "$LIGHTTPD_CONF_DIR/conf-available/pialert_front.conf" ] ; then
    sudo rm -r "$LIGHTTPD_CONF_DIR/conf-available/pialert_front.conf"                                           2>&1 >> "$LOG"
  fi
  sudo cp "$PIALERT_HOME/install/pialert_front.conf" "$LIGHTTPD_CONF_DIR/conf-available"                        2>&1 >> "$LOG"

  if [ -e "$LIGHTTPD_CONF_DIR/conf-enabled/pialert_front.conf" ] || \
     [ -L "$LIGHTTPD_CONF_DIR/conf-enabled/pialert_front.conf" ] ; then
    sudo rm -r "$LIGHTTPD_CONF_DIR/conf-enabled/pialert_front.conf"                                             2>&1 >> "$LOG"
  fi

  sudo ln -s ../conf-available/pialert_front.conf  "$LIGHTTPD_CONF_DIR/conf-enabled/pialert_front.conf"         2>&1 >> "$LOG"

  print_msg "- Restarting lighttpd..."

  sudo service lighttpd restart                                                                                 2>&1 >> "$LOG"
  # sudo /etc/init.d/lighttpd restart                             2>&1 >> "$LOG"
}

# ------------------------------------------------------------------------------
# Set Pi.Alert the default web server page
# ------------------------------------------------------------------------------
set_pialert_default_page() {
  if ! $PIALERT_DEFAULT_PAGE ; then
    return
  fi
  
  print_msg "- Setting Pi.Alert as default web server page..."

  if [ -e "$WEBROOT/index.lighttpd.html" ] ; then
    if [ -e "$WEBROOT/index.lighttpd.html.orig" ] ; then
      sudo rm "$WEBROOT/index.lighttpd.html"                                        2>&1 >> "$LOG"
    else
      sudo mv "$WEBROOT/index.lighttpd.html"  "$WEBROOT/index.lighttpd.html.orig"   2>&1 >> "$LOG"
    fi
  fi

  if [ -e "$WEBROOT/index.html" ] || [ -L "$WEBROOT/index.html" ] ; then
    if [ -e "$WEBROOT/index.html.orig" ] ; then
      sudo rm "$WEBROOT/index.html"                                                 2>&1 >> "$LOG"
    else
      sudo mv "$WEBROOT/index.html" "$WEBROOT/index.html.orig"                      2>&1 >> "$LOG"
    fi
  fi

  sudo cp "$PIALERT_HOME/install/index.html" "$WEBROOT/index.html"                  2>&1 >>"$LOG"
}

# ------------------------------------------------------------------------------
# Check Pi.Alert Installation Path
# ------------------------------------------------------------------------------
check_pialert_home() {
  mkdir -p "$INSTALL_DIR"
  if [ ! -d "$INSTALL_DIR" ] ; then
    process_error "Installation path does not exists: $INSTALL_DIR"
  fi

  if [ -e "$PIALERT_HOME" ] || [ -L "$PIALERT_HOME" ] ; then
    process_error "Pi.Alert path already exists: $PIALERT_HOME"
  fi
  sudo apt-get install cron whiptail -y
}

# ------------------------------------------------------------------------------
# Check Pi.Alert Installation Path
# ------------------------------------------------------------------------------
install_dependencies() {
  print_msg "- Installing dependencies..."
  if [ $(id -u) -eq 0 ]; then
      #apt-get update                                             2>&1 >> "$LOG"
      apt-get install sudo -y                                    2>&1 >> "$LOG"
  fi

  sudo apt-get install cron whiptail -y                          2>&1 >> "$LOG"
}

# ------------------------------------------------------------------------------
# Move Logfile
# ------------------------------------------------------------------------------
move_logfile() {
  NEWLOG="$PIALERT_HOME/log/$LOG"

  mkdir -p "$PIALERT_HOME/log"
  mv $LOG $NEWLOG

  LOG="$NEWLOG"
  NEWLOG=""
}

# ------------------------------------------------------------------------------
# ASK
# ------------------------------------------------------------------------------
msgbox() {
  LINE1=$(printf "%*s" $(((${#1}+$COLS-5)/2)) "$1")
  LINE2=$(printf "%*s" $(((${#2}+$COLS-5)/2)) "$2")

  END_DIALOG=false
  while ! $END_DIALOG ; do
    whiptail --title "Pi.Alert Installation" --msgbox "$LINE1\\n\\n$LINE2" $ROWS $COLS
    BUTTON=$?
    ask_cancel
    ANSWER=true
  done
}

ask_yesno() {
  LINE1=$(printf "%*s" $(((${#1}+$COLS-5)/2)) "$1")
  LINE2=$(printf "%*s" $(((${#2}+$COLS-5)/2)) "$2")

  if [ "$3" = "YES" ]; then
    DEF_BUTTON=""
  else
    DEF_BUTTON="--defaultno"
  fi

  END_DIALOG=false
  while ! $END_DIALOG ; do
    whiptail --title "Pi.Alert Installation" --yesno $DEF_BUTTON "$LINE1\\n\\n$LINE2" $ROWS $COLS
    BUTTON=$?
    ask_cancel
  done

  if [ "$BUTTON" = "0" ] ; then
    ANSWER=true
  else
    ANSWER=false
  fi
}

ask_option() {
  MENU_ARGS=("$@")
  MENU_ARGS=("${MENU_ARGS[@]:1}")

  END_DIALOG=false
  while ! $END_DIALOG ; do
    ANSWER=$(whiptail --title "Pi.Alert Installation" --menu "$1" $ROWS $COLS "${MENU_ARGS[@]}"  3>&2 2>&1 1>&3 )
    BUTTON=$?
    ask_cancel CANCEL
  done
}

ask_input() {
  LINE1=$(printf "%*s" $(((${#1}+$COLS-5)/2)) "$1")
  LINE2=$(printf "%*s" $(((${#2}+$COLS-5)/2)) "$2")

  END_DIALOG=false
  while ! $END_DIALOG ; do
    ANSWER=$(whiptail --title "Pi.Alert Installation" --inputbox "$LINE1\\n\\n$LINE2" $ROWS $COLS "$3" 3>&2 2>&1 1>&3 )
    BUTTON=$?
    ask_cancel CANCEL

    if $END_DIALOG && [ "$ANSWER" = "" ] ; then
      msgbox "" "You must enter a value"
      END_DIALOG=false
    fi
  done
}

ask_cancel() {
  LINE0="Do you want to cancel the installation process"
  LINE0=$(printf "\n\n%*s" $(((${#LINE0}+$COLS-5)/2)) "$LINE0")

  if [ "$BUTTON" = "1" ] && [ "$1" = "CANCEL" ] ; then BUTTON="255"; fi

  if [ "$BUTTON" = "255" ] ; then
    whiptail --title "Pi.Alert Installation" --yesno --defaultno "$LINE0" $ROWS $COLS

    if [ "$?" = "0" ] ; then
      process_error "Installation Aborted by User"
    fi
  else
    END_DIALOG=true
  fi
}

# ------------------------------------------------------------------------------
# Log
# ------------------------------------------------------------------------------
log() {
  echo "$1" | tee -a "$LOG"
}

log_no_screen () {
  echo "$1" >> "$LOG"
}

log_only_screen () {
  echo "$1"
}

print_msg() {
  log_no_screen ""
  log "$1"
}

print_superheader() {
  log ""
  log "############################################################"
  log " $1"
  log "############################################################"  
}

print_header() {
  log ""
  log "------------------------------------------------------------"
  log " $1"
  log "------------------------------------------------------------"
}

process_error() {
  log ""
  log "************************************************************"
  log "************************************************************"
  log "**            ERROR INSTALLING PI.ALERT                   **"
  log "************************************************************"
  log "************************************************************"
  log ""
  log "$1"
  log ""
  log "Use 'cat $LOG' to view installation log"
  log ""

  # msgbox "****** ERROR INSTALLING Pi.ALERT ******" "$1"
  exit 1
}

# ------------------------------------------------------------------------------
  main
  exit 0
