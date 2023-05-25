$(function() {
    $(".js-packeta-select-all").on('click', function(){
        toggleCheckboxes(this, true);
    });

    $(".js-packeta-unselect-all").on('click', function(){
        toggleCheckboxes(this, false);
    });
    function toggleCheckboxes(button, state) {
        $(button).closest('.js-packeta-checkbox-group').find(':checkbox').prop('checked', state)
    }
});
