<?php
session_start();

if ($_SESSION["login"] != 1) {
	header('Location: ./index.php');
	exit;
}
require 'php/server/db.php';
require 'php/templates/header.php';
require 'php/server/graph.php';
require 'php/server/journal.php';

$DBFILE = '../db/pialert.db';
OpenDB();

// Get Online Graph Arrays
$graph_arrays = array();
$graph_arrays = prepare_graph_arrays_history($SCANSOURCE);
$Pia_Graph_Device_Time = $graph_arrays[0];
$Pia_Graph_Device_Down = $graph_arrays[1];
$Pia_Graph_Device_All = $graph_arrays[2];
$Pia_Graph_Device_Online = $graph_arrays[3];
$Pia_Graph_Device_Arch = $graph_arrays[4];
?>

<!-- Page ------------------------------------------------------------------ -->
  <div class="content-wrapper">

<!-- Content header--------------------------------------------------------- -->
    <section class="content-header">
      <h1 id="pageTitle">
         <?=$pia_lang['PRE_Title']. ' / ' . $_SESSION[$SCANSOURCE];?> 
      </h1>
    </section>

<!-- Main content ---------------------------------------------------------- -->
    <section class="content">

<!-- top small box 1 ------------------------------------------------------- -->
      <div class="row">

<?php
function header_presence_all($visibility, $header_all, $header_selected) {
  global $pia_lang;
  $layout = calc_header_size($header_all, $header_selected);
  if (strtolower($visibility) == 0) {$hide = "hide_element";} else {$hide = "";}
  echo '<div class="'.$layout['lg'].' '.$layout['md'].' '.$layout['sm'].' '.$hide.'">
          <a href="#" onclick="javascript: getDevicesPresence(\'all\');">
          <div class="small-box bg-aqua">
            <div class="inner"><h3 id="devicesAll"> -- </h3><p class="infobox_label">'.$pia_lang['PRE_Shortcut_AllDevices'].'</p></div>
            <div class="icon"><i class="fa fa-laptop text-aqua-40"></i></div>
          </div>
          </a>
        </div>';
}
function header_presence_con($visibility, $header_all, $header_selected) {
  global $pia_lang;
  $layout = calc_header_size($header_all, $header_selected);
  if (strtolower($visibility) == 0) {$hide = "hide_element";} else {$hide = "";}
  echo '<div class="'.$layout['lg'].' '.$layout['md'].' '.$layout['sm'].' '.$hide.'">
          <a href="#" onclick="javascript: getDevicesPresence(\'connected\');">
            <div class="small-box bg-green">
              <div class="inner"> <h3 id="devicesConnected"> -- </h3><p class="infobox_label">'.$pia_lang['PRE_Shortcut_Connected'].'</p></div>
              <div class="icon"> <i class="mdi mdi-lan-connect text-green-40"></i> </div>
            </div>
          </a>
        </div>';
}
function header_presence_fav($visibility, $header_all, $header_selected) {
  global $pia_lang;
  $layout = calc_header_size($header_all, $header_selected);
  if (strtolower($visibility) == 0) {$hide = "hide_element";} else {$hide = "";}
  echo '<div class="'.$layout['lg'].' '.$layout['md'].' '.$layout['sm'].' '.$hide.'">
          <a href="#" onclick="javascript: getDevicesPresence(\'favorites\');">
            <div  class="small-box bg-yellow">
              <div class="inner"> <h3 id="devicesFavorites"> -- </h3><p class="infobox_label">'.$pia_lang['PRE_Shortcut_Favorites'].'</p></div>
              <div class="icon"> <i class="fa fa-star text-yellow-40"></i> </div>
            </div>
          </a>
        </div>';
}
function header_presence_new($visibility, $header_all, $header_selected) {
  global $pia_lang;
  $layout = calc_header_size($header_all, $header_selected);
  if (strtolower($visibility) == 0) {$hide = "hide_element";} else {$hide = "";}
  echo '<div class="'.$layout['lg'].' '.$layout['md'].' '.$layout['sm'].' '.$hide.'">
          <a href="#" onclick="javascript: getDevicesPresence(\'new\');">
            <div  class="small-box bg-yellow">
              <div class="inner"> <h3 id="devicesNew"> -- </h3><p class="infobox_label">'.$pia_lang['PRE_Shortcut_NewDevices'].'</p></div>
              <div class="icon"> <i class="fa fa-plus text-yellow-40"></i> </div>
            </div>
          </a>
        </div>';
}
function header_presence_dnw($visibility, $header_all, $header_selected) {
  global $pia_lang;
  $layout = calc_header_size($header_all, $header_selected);
  if (strtolower($visibility) == 0) {$hide = "hide_element";} else {$hide = "";}
  echo '<div class="'.$layout['lg'].' '.$layout['md'].' '.$layout['sm'].' '.$hide.'">
          <a href="#" onclick="javascript: getDevicesPresence(\'down\');">
            <div  class="small-box bg-red">
              <div class="inner"> <h3 id="devicesDown"> -- </h3><p class="infobox_label">'.$pia_lang['PRE_Shortcut_DownAlerts'].'</p></div>
              <div class="icon"> <i class="mdi mdi-lan-disconnect text-red-40"></i> </div>
            </div>
          </a>
        </div>';
}
function header_presence_arc($visibility, $header_all, $header_selected) {
  global $pia_lang;
  $layout = calc_header_size($header_all, $header_selected);
  if (strtolower($visibility) == 0) {$hide = "hide_element";} else {$hide = "";}
  echo '<div class="'.$layout['lg'].' '.$layout['md'].' '.$layout['sm'].' '.$hide.'">
          <a href="#" onclick="javascript: getDevicesPresence(\'archived\');">
            <div  class="small-box bg-gray top_small_box_gray_text">
              <div class="inner"> <h3 id="devicesHidden"> -- </h3><p class="infobox_label">'.$pia_lang['PRE_Shortcut_Archived'].'</p></div>
              <div class="icon"> <i class="fa fa-eye-slash text-gray-40"></i> </div>
            </div>
          </a>
        </div>';
}
$header_page_config = read_HeaderConfig();
$count_active_headers = count(array_filter($header_page_config['presence'], function($value) {
    return $value == 1;
}));
header_presence_all($header_page_config['presence']['all'], sizeof($header_page_config['presence']), $count_active_headers);
header_presence_con($header_page_config['presence']['con'], sizeof($header_page_config['presence']), $count_active_headers);
header_presence_fav($header_page_config['presence']['fav'], sizeof($header_page_config['presence']), $count_active_headers);
header_presence_new($header_page_config['presence']['new'], sizeof($header_page_config['presence']), $count_active_headers);
header_presence_dnw($header_page_config['presence']['dnw'], sizeof($header_page_config['presence']), $count_active_headers);
header_presence_arc($header_page_config['presence']['arc'], sizeof($header_page_config['presence']), $count_active_headers);
?>
      </div>

<!-- Activity Chart ------------------------------------------------------- -->

<?php
If ($ENABLED_HISTOY_GRAPH !== False) {
	?>
      <div class="row">
          <div class="col-md-12">
          <div class="box" id="clients">
              <div class="box-header with-border">
                <h3 class="box-title"><?=$pia_lang['Device_Shortcut_OnlineChart_a'];?><span class="maxlogage-interval">12</span> <?=$pia_lang['Device_Shortcut_OnlineChart_b'];?></h3>
              </div>
              <div class="box-body">
                <div class="chart">
                  <script src="lib/AdminLTE/bower_components/chart.js/Chart.js"></script>
                  <canvas id="OnlineChart" style="width:100%; height: 150px;  margin-bottom: 15px;"></canvas>
                </div>
              </div>
            </div>
          </div>
      </div>

      <script src="js/graph_online_history.js"></script>
      <script>
        var pia_js_online_history_time = [<?php pia_graph_devices_data($Pia_Graph_Device_Time);?>];
        var pia_js_online_history_ondev = [<?php pia_graph_devices_data($Pia_Graph_Device_Online);?>];
        var pia_js_online_history_dodev = [<?php pia_graph_devices_data($Pia_Graph_Device_Down);?>];
        var pia_js_online_history_ardev = [<?php pia_graph_devices_data($Pia_Graph_Device_Arch);?>];
        graph_online_history_main(pia_js_online_history_time, pia_js_online_history_ondev, pia_js_online_history_dodev, pia_js_online_history_ardev);
      </script>
<?php
}
?>

<!-- Calendar -------------------------------------------------------------- -->
      <div class="row">
        <div class="col-lg-12 col-sm-12 col-xs-12">
          <div id="tableDevicesBox" class="box" style="min-height: 500px">

            <!-- box-header -->
            <div class="box-header">
              <h3 id="tableDevicesTitle" class="box-title text-gray">Devices</h3>
            </div>

            <!-- box-body -->
            <div class="box-body table-responsive">

              <!-- spinner -->
              <div id="loading" style="display: none">
                <div class="pa_semitransparent-panel"></div>
                <div class="panel panel-default pa_spinner">
                  <table>
                    <td width="130px" align="middle">Loading...</td>
                    <td><i class="ion ion-ios-sync fa-spin fa-2x fa-fw"></td>
                  </table>
                </div>
              </div>

              <!-- Calendar -->
              <div id="calendar"></div>
            </div>

          </div>
          <!-- /.box -->
        </div>
        <!-- /.col -->
      </div>
      <!-- /.row -->

<!-- ----------------------------------------------------------------------- -->
    </section>
    <!-- /.content -->
  </div>
  <!-- /.content-wrapper -->

<!-- ----------------------------------------------------------------------- -->
<?php
require 'php/templates/footer.php';
?>

<!-- ----------------------------------------------------------------------- -->
<!-- fullCalendar -->
  <link rel="stylesheet" href="lib/AdminLTE/bower_components/fullcalendar/dist/fullcalendar.min.css">
  <link rel="stylesheet" href="lib/AdminLTE/bower_components/fullcalendar/dist/fullcalendar.print.min.css" media="print">
  <script src="lib/AdminLTE/bower_components/moment/moment.js"></script>
  <script src="lib/AdminLTE/bower_components/fullcalendar/dist/fullcalendar.min.js"></script>
  <script src="lib/AdminLTE/bower_components/fullcalendar/dist/locale-all.js"></script>

<!-- fullCalendar Scheduler -->
  <link href="lib/fullcalendar-scheduler/scheduler.min.css" rel="stylesheet">
  <script src="lib/fullcalendar-scheduler/scheduler.min.js"></script>

<!-- Dark-Mode Patch -->
<?php
if ($ENABLED_DARKMODE === True) {
	echo '<link rel="stylesheet" href="css/dark-patch-cal.css">';
}
?>

<!-- page script ----------------------------------------------------------- -->
<script>

  var deviceStatus = 'all';

  // Read parameters & Initialize components
  main();

// -----------------------------------------------------------------------------
function main () {
  // Initialize components
  $(function () {
    initializeCalendar();
    getDevicesTotals();
    getDevicesPresence(deviceStatus);
  });

  // Force re-render calendar on tab change (bugfix for render error at left panel)
  $(document).on('shown.bs.tab', 'a[data-toggle="tab"]', function (nav) {
    if ($(nav.target).attr('href') == '#panPresence') {
      $('#calendar').fullCalendar('rerenderEvents');
    }
  });
}

// -----------------------------------------------------------------------------
function initializeCalendar () {
  $('#calendar').fullCalendar({
    header: {
      left            : 'prev,next today',
      center          : 'title',
      right           : 'timelineYear,timelineMonth,timelineWeek,timelineDay'
    },
    defaultView       : 'timelineMonth',
    height            : 'auto',
    firstDay          : 1,
    allDaySlot        : false,
    timeFormat        : 'H:mm',

    resourceLabelText : '<?=$pia_lang['PRE_CallHead_Devices'];?>',
    resourceAreaWidth : '160px',
    slotWidth         : '1px',

    resourceOrder     : '-favorite,title',
    locale            : '<?=$pia_lang['PRE_CalHead_lang'];?>',

    //schedulerLicenseKey: 'CC-Attribution-NonCommercial-NoDerivatives',
    schedulerLicenseKey: 'GPL-My-Project-Is-Open-Source',

    views: {
      timelineYear: {
        type              : 'timeline',
        duration          : { year: 1 },
        buttonText        : '<?=$pia_lang['PRE_CalHead_year'];?>',
        slotLabelFormat   : 'MMM',
        // Hack to show partial day events not as fullday events
        slotDuration      : {minutes: 44641}
      },
      timelineQuarter: {
        type              : 'timeline',
        duration          : { month: 3 },
        buttonText        : '<?=$pia_lang['PRE_CalHead_quarter'];?>',
        slotLabelFormat   : 'MMM',
        // Hack to show partial day events not as fullday events
        slotDuration      : {minutes: 44641}
      },
      timelineMonth: {
        type              : 'timeline',
        duration          : { month: 1 },
        buttonText        : '<?=$pia_lang['PRE_CalHead_month'];?>',
        slotLabelFormat   : 'D',
        // Hack to show partial day events not as fullday events
        slotDuration      : '24:00:01'
      },
      timelineWeek: {
        type              : 'timeline',
        duration          : { week: 1 },
        buttonText        : '<?=$pia_lang['PRE_CalHead_week'];?>',
        slotLabelFormat   : 'D',
        slotDuration      : '24:00:01'
      },
      timelineDay: {
        type              : 'timeline',
        duration          : { day: 1 },
        buttonText        : '<?=$pia_lang['PRE_CalHead_day'];?>',
        slotLabelFormat   : 'H',
        slotDuration      : '00:30:00'
      }
    },

    // Needed due hack partial day events 23:59:59
    dayRender: function (date, cell) {
      if ($('#calendar').fullCalendar('getView').name == 'timelineYear') {
        cell.removeClass('fc-sat');
        cell.removeClass('fc-sun');
        return;
      };

      if (date.day() == 0) {
        cell.addClass('fc-sun'); };

      if (date.day() == 6) {
        cell.addClass('fc-sat'); };

      if (date.format('YYYY-MM-DD') == moment().format('YYYY-MM-DD')) {
          cell.addClass ('fc-today'); };

      if ($('#calendar').fullCalendar('getView').name == 'timelineDay') {
        cell.removeClass('fc-sat');
        cell.removeClass('fc-sun');
        cell.removeClass('fc-today');
        if (date.format('YYYY-MM-DD HH') == moment().format('YYYY-MM-DD HH')) {
          cell.addClass('fc-today');
        }
      };

    },

    resourceRender: function (resourceObj, labelTds, bodyTds) {
      labelTds.find('span.fc-cell-text').html (
      '<b><a href="deviceDetails.php?mac='+ resourceObj.id+ '" class="">'+ resourceObj.title +'</a></b>');

      // Resize heihgt
      // $(".fc-content table tbody tr .fc-widget-content div").addClass('fc-resized-row');
    },

    eventRender: function (event, element, view) {
      $(element).tooltip({container: 'body', placement: 'bottom', title: event.tooltip});
      // element.attr ('title', event.tooltip);  // Alternative tooltip
    },

    loading: function( isLoading, view ) {
        if (isLoading) {
          $("#loading").show();
        } else {
          $("#loading").hide();
        }
    }

  })
}

// -----------------------------------------------------------------------------
function getDevicesTotals () {
  // stop timer
  stopTimerRefreshData();

  // get totals and put in boxes
  $.get('php/server/devices.php?action=getDevicesTotals&scansource=<?=$SCANSOURCE?>', function(data) {
    var totalsDevices = JSON.parse(data);

    $('#devicesAll').html        (totalsDevices[0].toLocaleString());
    $('#devicesConnected').html  (totalsDevices[1].toLocaleString());
    $('#devicesFavorites').html  (totalsDevices[2].toLocaleString());
    $('#devicesNew').html        (totalsDevices[3].toLocaleString());
    $('#devicesDown').html       (totalsDevices[4].toLocaleString());
    $('#devicesHidden').html     (totalsDevices[5].toLocaleString());

    // Timer for refresh data
    newTimerRefreshData (getDevicesTotals);
  } );
}

// -----------------------------------------------------------------------------
function getDevicesPresence (status) {
  // Save status selected
  deviceStatus = status;

  // Defini color & title for the status selected
  switch (deviceStatus) {
    case 'all':        tableTitle = '<?=$pia_lang['PRE_Shortcut_AllDevices'];?>';    color = 'aqua';    break;
    case 'connected':  tableTitle = '<?=$pia_lang['PRE_Shortcut_Connected'];?>';     color = 'green';   break;
    case 'favorites':  tableTitle = '<?=$pia_lang['PRE_Shortcut_Favorites'];?>';     color = 'yellow';  break;
    case 'new':        tableTitle = '<?=$pia_lang['PRE_Shortcut_NewDevices'];?>';    color = 'yellow';  break;
    case 'down':       tableTitle = '<?=$pia_lang['PRE_Shortcut_DownAlerts'];?>';    color = 'red';     break;
    case 'archived':   tableTitle = '<?=$pia_lang['PRE_Shortcut_Archived'];?>';      color = 'gray';    break;
    default:           tableTitle = '<?=$pia_lang['PRE_Shortcut_Devices'];?>';       color = 'gray';    break;
  }

  // Set title and color
  $('#tableDevicesTitle')[0].className = 'box-title text-'+ color;
  $('#tableDevicesBox')[0].className = 'box box-'+ color;
  $('#tableDevicesTitle').html (tableTitle);

  // Define new datasource URL and reload
  $('#calendar').fullCalendar ('option', 'resources', 'php/server/devices.php?action=getDevicesListCalendar&scansource=<?=$SCANSOURCE?>&status='+ deviceStatus);
  $('#calendar').fullCalendar ('refetchResources');

  $('#calendar').fullCalendar('removeEventSources');
  $('#calendar').fullCalendar('addEventSource', { url: 'php/server/events.php?action=getEventsCalendar&scansource=<?=$SCANSOURCE?>' });
};

</script>
