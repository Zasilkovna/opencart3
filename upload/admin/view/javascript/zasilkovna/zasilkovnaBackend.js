$(function() {
    $(".js-packeta-select-all").on('click', function(){
        toggleCheckboxes(this, true);
    });

    $(".js-packeta-unselect-all").on('click', function(){
        toggleCheckboxes(this, false);
    });

    $('.js-delete').on('click', function(){
        let confirmText = $(this).data('confirm');
        return confirm(confirmText)
    });

    function toggleCheckboxes(button, state) {
        $(button).closest('.js-packeta-checkbox-group').find(':checkbox').prop('checked', state)
    }
});
