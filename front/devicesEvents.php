<?php
session_start();

if ($_SESSION["login"] != 1) {
	header('Location: ./index.php');
	exit;
}
require 'php/templates/header.php';
require 'php/server/journal.php';

?>

  <div class="content-wrapper">

    <section class="content-header">
      <h1 id="pageTitle">
         <?=$pia_lang['EVE_Title'];?>
      </h1>

      <!-- period selector -->
      <span class="breadcrumb" style="top: 0px;">
        <select class="form-control" id="period" onchange="javascript: periodChanged();">
          <option value="1 day"><?=$pia_lang['EVE_Periodselect_today'];?></option>
          <option value="7 days"><?=$pia_lang['EVE_Periodselect_LastWeek'];?></option>
          <option value="1 month" selected><?=$pia_lang['EVE_Periodselect_LastMonth'];?></option>
          <option value="1 year"><?=$pia_lang['EVE_Periodselect_LastYear'];?></option>
          <option value="100 years"><?=$pia_lang['EVE_Periodselect_All'];?></option>
        </select>
      </span>
    </section>

    <section class="content">

<!-- top small box --------------------------------------------------------- -->
      <div class="row">

        <div class="col-lg-2 col-sm-4 col-xs-6">
          <a href="#" onclick="javascript: getEvents('all');">
            <div class="small-box bg-aqua">
              <div class="inner"> <h3 id="eventsAll"> -- </h3>
                <p class="infobox_label"><?=$pia_lang['EVE_Shortcut_AllEvents'];?></p>
              </div>
              <div class="icon"> <i class="fa fa-bolt text-aqua-40"></i> </div>
            </div>
          </a>
        </div>

        <div class="col-lg-2 col-sm-4 col-xs-6">
          <a href="#" onclick="javascript: getEvents('sessions');">
            <div class="small-box bg-green">
              <div class="inner"> <h3 id="eventsSessions"> -- </h3>
                <p class="infobox_label"><?=$pia_lang['EVE_Shortcut_Sessions'];?></p>
              </div>
              <div class="icon"> <i class="mdi mdi-lan-connect text-green-40"></i> </div>
            </div>
          </a>
        </div>

        <div class="col-lg-2 col-sm-4 col-xs-6">
          <a href="#" onclick="javascript: getEvents('missing');">
            <div  class="small-box bg-yellow">
              <div class="inner"> <h3 id="eventsMissing"> -- </h3>
                <p class="infobox_label"><?=$pia_lang['EVE_Shortcut_MissSessions'];?></p>
              </div>
              <div class="icon"> <i class="fa fa-exchange text-yellow-40"></i> </div>
            </div>
          </a>
        </div>

        <div class="col-lg-2 col-sm-4 col-xs-6">
          <a href="#" onclick="javascript: getEvents('voided');">
            <div  class="small-box bg-yellow">
              <div class="inner"> <h3 id="eventsVoided"> -- </h3>
                <p class="infobox_label"><?=$pia_lang['EVE_Shortcut_VoidSessions'];?></p>
              </div>
              <div class="icon"> <i class="fa fa-exclamation-circle text-yellow-40"></i> </div>
            </div>
          </a>
        </div>

        <div class="col-lg-2 col-sm-4 col-xs-6">
          <a href="#" onclick="javascript: getEvents('new');">
            <div  class="small-box bg-yellow">
              <div class="inner"> <h3 id="eventsNewDevices"> -- </h3>
                <p class="infobox_label"><?=$pia_lang['EVE_Shortcut_NewDevices'];?></p>
              </div>
              <div class="icon"> <i class="fa fa-plus text-yellow-40"></i> </div>
            </div>
          </a>
        </div>

        <div class="col-lg-2 col-sm-4 col-xs-6">
          <a href="#" onclick="javascript: getEvents('down');">
            <div  class="small-box bg-red">
              <div class="inner"> <h3 id="eventsDown"> -- </h3>
                <p class="infobox_label"><?=$pia_lang['EVE_Shortcut_DownAlerts'];?></p>
              </div>
              <div class="icon"> <i class="mdi mdi-lan-disconnect text-red-40"></i> </div>
            </div>
          </a>
        </div>

      </div>
      <!-- /.row -->

<!-- datatable ------------------------------------------------------------- -->
      <div class="row">
        <div class="col-xs-12">
          <div id="tableEventsBox" class="box">

            <!-- box-header -->
            <div class="box-header">
              <h3 id="tableEventsTitle" class="box-title text-gray">Events</h3>
            </div>

            <!-- table -->
            <div class="box-body table-responsive">
              <table id="tableEvents" class="table table-bordered table-hover table-striped ">
                <thead>
                <tr>
                  <th><?=$pia_lang['EVE_TableHead_Order'];?></th>
                  <th><?=$pia_lang['EVE_TableHead_Device'];?></th>
                  <th><?=$pia_lang['EVE_TableHead_Owner'];?></th>
                  <th><?=$pia_lang['EVE_TableHead_Date'];?></th>
                  <th><?=$pia_lang['EVE_TableHead_EventType'];?></th>
                  <th><?=$pia_lang['EVE_TableHead_Connection'];?></th>
                  <th><?=$pia_lang['EVE_TableHead_Disconnection'];?></th>
                  <th><?=$pia_lang['EVE_TableHead_Duration'];?></th>
                  <th><?=$pia_lang['EVE_TableHead_DurationOrder'];?></th>
                  <th><?=$pia_lang['EVE_TableHead_IP'];?></th>
                  <th><?=$pia_lang['EVE_TableHead_IPOrder'];?></th>
                  <th><?=$pia_lang['EVE_TableHead_AdditionalInfo'];?></th>
                </tr>
                </thead>
              </table>
            </div>
            <!-- /.box-body -->

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

<!-- Datatable -->
<link rel="stylesheet" href="lib/AdminLTE/bower_components/datatables.net-bs/css/dataTables.bootstrap.min.css">
<script src="lib/AdminLTE/bower_components/datatables.net/js/jquery.dataTables.min.js"></script>
<script src="lib/AdminLTE/bower_components/datatables.net-bs/js/dataTables.bootstrap.min.js"></script>

<!-- page script ----------------------------------------------------------- -->
<script>
  var parPeriod       = 'Front_Events_Period';
  var parTableRows    = 'Front_Events_Rows';

  var eventsType      = 'all';
  var period          = '';
  var tableRows       = 50;

  // Read parameters & Initialize components
  main();

// -----------------------------------------------------------------------------
function main () {
  // get parameter value
  $.get('php/server/parameters.php?action=get&parameter='+ parPeriod, function(data) {
    var result = JSON.parse(data);
    if (result) {
      period = result;
      $('#period').val(period);
    }

    // get parameter value
    $.get('php/server/parameters.php?action=get&parameter='+ parTableRows, function(data) {
      var result = JSON.parse(data);
      if (Number.isInteger (result) ) {
          tableRows = result;
      }

      // Initialize components
      initializeDatatable();

      // query data
      getEventsTotals();
      getEvents (eventsType);
    });
  });
}

// -----------------------------------------------------------------------------
function initializeDatatable () {
  $('#tableEvents').DataTable({
    'paging'       : true,
    'lengthChange' : true,
    'lengthMenu'   : [[10, 25, 50, 100, 500, -1], [10, 25, 50, 100, 500, 'All']],
    'searching'    : true,
    'ordering'     : true,
    'info'         : true,
    'autoWidth'    : false,
    'order'       : [[0,"desc"], [3,"desc"], [5,"desc"]],

    // Parameters
    'pageLength'   : tableRows,

    'columnDefs'  : [
      {visible:   false,         targets: [0,5,6,7,8,10] },
      {className: 'text-center', targets: [] },
      {orderData: [8],           targets: 7 },
      {orderData: [10],          targets: 9 },

      // Device Name
      {targets: [1],
        "createdCell": function (td, cellData, rowData, row, col) {
          // $(td).html ('<b><a href="deviceDetails.php?mac='+ rowData[13] +'" class="">'+ cellData +'</a></b>');
          if (rowData[13]) {
              $(td).html('<b><a href="deviceDetails.php?mac=' + rowData[13] + '" class="">' + cellData + '</a></b>');
          } else {
              // $(td).html('<b><a href="icmpmonitorDetails.php?hostip=' + rowData[9] + '" class="">' + cellData + '</a></b>');

              if (String(cellData).endsWith("**")) {
                  const mainText = String(cellData).slice(0, -2);

                  $(td).html(
                      '<b><a href="icmpmonitorDetails.php?hostip=' + rowData[9] + '" class="">' +
                      mainText +
                      '<span class="text-warning">**</span>' +
                      '</a></b>'
                  );
              } else {
                  // Standardfall, keine Hervorhebung nötig
                  $(td).html('<b><a href="icmpmonitorDetails.php?hostip=' + rowData[9] + '" class="">' + cellData + '</a></b>');
              }



          }
      } },

      // Replace HTML codes
      {targets: [3,4,5,6,7],
        "createdCell": function (td, cellData, rowData, row, col) {
          $(td).html (translateHTMLcodes (cellData));
      } }
    ],

    // Processing
    'processing'  : true,
    'language'    : {
      processing: '<table><td width="130px" align="middle">Loading...</td><td><i class="ion ion-ios-sync fa-spin fa-2x fa-fw"></td></table>',
      emptyTable: 'No data',
      "lengthMenu": "<?=$pia_lang['EVE_Tablelenght'];?>",
      "search":     "<?=$pia_lang['EVE_Searchbox'];?>: ",
      "paginate": {
          "next":       "<?=$pia_lang['EVE_Table_nav_next'];?>",
          "previous":   "<?=$pia_lang['EVE_Table_nav_prev'];?>"
      },
      "info":           "<?=$pia_lang['EVE_Table_info'];?>",
    }
  });

  // Save Parameter rows when changed
  $('#tableEvents').on( 'length.dt', function ( e, settings, len ) {
    setParameter (parTableRows, len);
  } );
};

// -----------------------------------------------------------------------------
function periodChanged () {
  // Save Parameter Period
  period = $('#period').val();
  setParameter (parPeriod, period);

  // Requery totals and events
  getEventsTotals();
  getEvents (eventsType);
}

// -----------------------------------------------------------------------------
function getEventsTotals () {
  // stop timer
  stopTimerRefreshData();

  // get totals and put in boxes
  $.get('php/server/events.php?action=getEventsTotals&period='+ period, function(data) {
    var totalsEvents = JSON.parse(data);

    $('#eventsAll').html        (totalsEvents[0].toLocaleString());
    $('#eventsSessions').html   (totalsEvents[1].toLocaleString());
    $('#eventsMissing').html    (totalsEvents[2].toLocaleString());
    $('#eventsVoided').html     (totalsEvents[3].toLocaleString());
    $('#eventsNewDevices').html (totalsEvents[4].toLocaleString());
    $('#eventsDown').html       (totalsEvents[5].toLocaleString());

    // Timer for refresh data
    newTimerRefreshData (getEventsTotals);
  });
}

// -----------------------------------------------------------------------------
function getEvents (p_eventsType) {
  // Save status selected
  eventsType = p_eventsType;

  // Define color & title for the status selected
  switch (eventsType) {
    case 'all':       tableTitle = '<?=$pia_lang['EVE_Shortcut_AllEvents'];?>';      color = 'aqua';    sesionCols = false;  break;
    case 'sessions':  tableTitle = '<?=$pia_lang['EVE_Shortcut_Sessions'];?>';       color = 'green';   sesionCols = true;   break;
    case 'missing':   tableTitle = '<?=$pia_lang['EVE_Shortcut_MissSessions'];?>';   color = 'yellow';  sesionCols = true;   break;
    case 'voided':    tableTitle = '<?=$pia_lang['EVE_Shortcut_VoidSessions'];?>';   color = 'yellow';  sesionCols = false;  break;
    case 'new':       tableTitle = '<?=$pia_lang['EVE_Shortcut_NewDevices'];?>';     color = 'yellow';  sesionCols = false;  break;
    case 'down':      tableTitle = '<?=$pia_lang['EVE_Shortcut_DownAlerts'];?>';     color = 'red';     sesionCols = false;  break;
    default:          tableTitle = '<?=$pia_lang['EVE_Shortcut_Events'];?>';         boxClass = '';     sesionCols = false;  break;
  }

  // Set title and color
  $('#tableEventsTitle')[0].className = 'box-title text-' + color;
  $('#tableEventsBox')[0].className = 'box box-' + color;
  $('#tableEventsTitle').html (tableTitle);

  // Coluumns Visibility
  $('#tableEvents').DataTable().column(3).visible (!sesionCols);
  $('#tableEvents').DataTable().column(4).visible (!sesionCols);
  $('#tableEvents').DataTable().column(5).visible (sesionCols);
  $('#tableEvents').DataTable().column(6).visible (sesionCols);
  $('#tableEvents').DataTable().column(7).visible (sesionCols);

  // Define new datasource URL and reload
  $('#tableEvents').DataTable().clear();
  $('#tableEvents').DataTable().draw();
  $('#tableEvents').DataTable().order ([0,"desc"], [3,"desc"], [5,"desc"]);
  $('#tableEvents').DataTable().ajax.url('php/server/events.php?action=getEvents&type=' + eventsType +'&period='+ period ).load();
};

</script>
