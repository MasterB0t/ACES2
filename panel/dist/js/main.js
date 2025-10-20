var TABLE;
var TABLEURL = '';


function _activeFilters() {
    var params = new URL(window.location.href).searchParams;
    for(const [ key, value ] of params){
        if(key.includes('filter_')) {
            selectname = key.split('filter_')[1];
            $("#formFilters select[name='"+selectname+"']").val(value)
            $("#formFilters select[name='"+selectname+"']").change();
            $(".divFilters").show();
        }
    }
}



function TABLE_RELOAD() { TABLE.ajax.reload() }

$("#formFilters select").change(function(){

    args = "";

    $('#formFilters select').each(function(){
        if($(this).val()) {
            name = $(this).attr('name');
            args += "&filter_" + name + "=" + $(this).val()
        }
    });

    TABLE.ajax.url(TABLEURL+"?"+args);
    TABLE.ajax.reload();
    TABLE.page(0);

});

$("#toggleFilter").click(function() {
    //$(".divFilters").toggle();
});

$(document).ready(function(){


    //_activeFilters();
    $("body").fadeIn();

    if($(".select2").length)
        $(".select2").select2();

    if( $('.bootstrap-switch').length )
        $('.bootstrap-switch').bootstrapSwitch();

});

$( window ).on( "resize", function() {
    if($(".select2").length)
        $(".select2").select2();
});

