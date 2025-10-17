/**
 * PSAU Admission System - Course Selection JavaScript
 * Handles the dynamic behavior of the course selection form
 */

document.addEventListener('DOMContentLoaded', function() {
    // Get select elements
    const firstSelect = document.querySelector('select[name="first_choice"]');
    const secondSelect = document.querySelector('select[name="second_choice"]');
    const thirdSelect = document.querySelector('select[name="third_choice"]');
    
    if (firstSelect && secondSelect && thirdSelect) {
        // Function to update available options
        function updateOptions() {
            const firstValue = firstSelect.value;
            const secondValue = secondSelect.value;
            
            // Reset all options to be enabled
            for (let select of [firstSelect, secondSelect, thirdSelect]) {
                for (let option of select.options) {
                    if (option.value) { // Skip empty option
                        option.disabled = false;
                    }
                }
            }
            
            // Disable options that are already selected in other dropdowns
            if (firstValue) {
                disableOption(secondSelect, firstValue);
                disableOption(thirdSelect, firstValue);
            }
            
            if (secondValue) {
                disableOption(firstSelect, secondValue);
                disableOption(thirdSelect, secondValue);
            }
            
            if (thirdSelect.value) {
                disableOption(firstSelect, thirdSelect.value);
                disableOption(secondSelect, thirdSelect.value);
            }
        }
        
        // Helper function to disable an option in a select
        function disableOption(select, value) {
            for (let option of select.options) {
                if (option.value === value) {
                    option.disabled = true;
                }
            }
        }
        
        // Add event listeners
        firstSelect.addEventListener('change', updateOptions);
        secondSelect.addEventListener('change', updateOptions);
        thirdSelect.addEventListener('change', updateOptions);
        
        // Initialize on page load
        updateOptions();
    }
}); 