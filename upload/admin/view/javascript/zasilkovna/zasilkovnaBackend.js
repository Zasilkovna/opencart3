function toggleAllParentCheckboxes(element, newState) {
    $(element).parent().find(':checkbox').prop('checked', newState)
}
