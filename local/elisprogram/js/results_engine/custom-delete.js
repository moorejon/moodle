/*
 * This custom delete row function is called anytime a user clicks the "X"
 * button to delete a row in resultsform.class.php.  The built-in deleteRow
 * javascript function removes the row from the DOM, but the Moodle forms.lib
 * tries to add the missing input values back into data array for validation.
 * This function hides the entire row and sets all input values to null.  The
 * form validation function in resultsform.class.php will then skip over these
 * values.  -- IQ 9/22/14
 */
function customDeleteRow(deleteButton){
    //find the parent row of this the button clicked
    $(deleteButton).parents('tr').css('display', 'none');
    //find all of the form elements with input values
    var sibs = $(deleteButton).parents('tr').find('input');
    //cycle through each of these and set each to null
    sibs.each(function (index){
        if ($(this).attr('value') !== undefined){
            $(this).attr('value', '');
        }
    });
}