$(function() {
    google.charts.load('current', {'packages': ['table']});
    google.charts.setOnLoadCallback(drawTable);
    function drawTable() {
        $.get( "/index.php/entries", function( jsonData ) {
            var data = new google.visualization.DataTable(jsonData);
            var table = new google.visualization.Table(document.getElementById('table_div'));
            table.draw(data, {width: '100%', height: '100%'});
        });
    }
});
