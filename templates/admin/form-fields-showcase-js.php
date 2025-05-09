<?php
/**
 * JavaScript für die Form-Fields-Showcase
 */

// Verhindere direkten Zugriff
if (!defined('ABSPATH')) {
    exit;
}
?>

<script>
// Funktion zum Anzeigen und Ausblenden der durchsuchbaren Dropdown-Liste
function toggleSearchableDropdown(dropdownId) {
    const dropdown = document.getElementById(dropdownId);
    if (dropdown) {
        if (dropdown.classList.contains('hidden')) {
            // Alle anderen Dropdowns schließen
            document.querySelectorAll('.absolute.z-10:not(.hidden)').forEach(el => {
                if (el.id !== dropdownId) {
                    el.classList.add('hidden');
                }
            });
            
            // Dieses Dropdown öffnen
            dropdown.classList.remove('hidden');
            
            // Fokus auf das Suchfeld setzen
            const searchInput = dropdown.querySelector('input[type="text"]');
            if (searchInput) {
                setTimeout(() => {
                    searchInput.focus();
                }, 100);
            }
            
            // Event-Listener für Klicks außerhalb des Dropdowns hinzufügen
            document.addEventListener('click', closeDropdownOnClickOutside);
        } else {
            // Dropdown schließen
            dropdown.classList.add('hidden');
            document.removeEventListener('click', closeDropdownOnClickOutside);
        }
    }
}

// Funktion zum Schließen des Dropdowns, wenn außerhalb geklickt wird
function closeDropdownOnClickOutside(event) {
    const dropdowns = document.querySelectorAll('.absolute.z-10:not(.hidden)');
    dropdowns.forEach(dropdown => {
        const isClickInside = dropdown.contains(event.target);
        const button = document.querySelector(`[onclick="toggleSearchableDropdown('${dropdown.id}')"]`);
        const isClickOnButton = button && button.contains(event.target);
        
        if (!isClickInside && !isClickOnButton) {
            dropdown.classList.add('hidden');
            document.removeEventListener('click', closeDropdownOnClickOutside);
        }
    });
}

// Funktion zum Filtern der durchsuchbaren Optionen
function filterSearchableOptions(input, optionsListId) {
    const filter = input.value.toLowerCase();
    const options = document.getElementById(optionsListId).getElementsByTagName('li');
    
    for (let i = 0; i < options.length; i++) {
        const text = options[i].textContent || options[i].innerText;
        const value = options[i].getAttribute('data-value') || '';
        
        if (text.toLowerCase().indexOf(filter) > -1 || value.toLowerCase().indexOf(filter) > -1) {
            options[i].style.display = "";
        } else {
            options[i].style.display = "none";
        }
    }
}

// Funktion zur Auswahl einer Option im durchsuchbaren Select
document.addEventListener('DOMContentLoaded', function() {
    // Alle durchsuchbaren Select-Listen initialisieren
    setupSearchableSelect('searchable_select_options', 'searchable_select', 'searchable_select_dropdown');
    
    // Initialisierung für Multi-Select
    setupMultiSelect();
    
    // Initialisierung für Toggle-Switches
    setupToggleSwitches();
});

// Funktion zum Einrichten des durchsuchbaren Selects
function setupSearchableSelect(optionsListId, hiddenInputId, dropdownId) {
    const optionsList = document.getElementById(optionsListId);
    const hiddenInput = document.getElementById(hiddenInputId);
    const dropdown = document.getElementById(dropdownId);
    
    if (!optionsList || !hiddenInput) return;
    
    // Event-Listener für Klicks auf Optionen
    const options = optionsList.querySelectorAll('li');
    options.forEach(option => {
        option.addEventListener('click', function() {
            const value = this.getAttribute('data-value') || '';
            const text = this.textContent || this.innerText;
            
            // Wert in verstecktes Input-Feld setzen
            hiddenInput.value = value;
            
            // Anzeige-Text aktualisieren
            const displayElement = document.querySelector(`[onclick="toggleSearchableDropdown('${dropdownId}')"] span.block.truncate`);
            if (displayElement) {
                displayElement.textContent = text;
            }
            
            // Dropdown schließen
            dropdown.classList.add('hidden');
            document.removeEventListener('click', closeDropdownOnClickOutside);
            
            // Optional: Event auslösen, um auf Änderungen zu reagieren
            const event = new Event('change', { bubbles: true });
            hiddenInput.dispatchEvent(event);
        });
    });
}

// Funktion zum Einrichten des Multi-Selects mit Tags
function setupMultiSelect() {
    const multiSelectInput = document.getElementById('multi_select_input');
    const multiSelectDropdown = document.getElementById('multi_select_dropdown');
    const multiSelectHiddenInput = document.getElementById('multi_select');
    
    if (!multiSelectInput || !multiSelectDropdown || !multiSelectHiddenInput) return;
    
    // Fokus auf Input = Dropdown öffnen
    multiSelectInput.addEventListener('focus', function() {
        multiSelectDropdown.classList.remove('hidden');
    });
    
    // Klick auf Tag-Optionen
    const tagOptions = multiSelectDropdown.querySelectorAll('li');
    tagOptions.forEach(option => {
        option.addEventListener('click', function() {
            const text = this.textContent.trim();
            addTag(text);
            multiSelectInput.value = '';
            // Optionales Schließen des Dropdowns nach Auswahl
            // multiSelectDropdown.classList.add('hidden');
        });
    });
    
    // Klick auf "X" zum Entfernen von Tags
    document.addEventListener('click', function(e) {
        if (e.target.closest('.inline-flex.items-center button')) {
            const tagElement = e.target.closest('.inline-flex.items-center');
            if (tagElement) {
                tagElement.remove();
                updateMultiSelectValue();
            }
        }
    });
    
    // Funktion zum Hinzufügen eines Tags
    function addTag(text) {
        // Prüfen, ob der Tag bereits existiert
        const existingTags = document.querySelectorAll('.inline-flex.items-center');
        for (let i = 0; i < existingTags.length; i++) {
            if (existingTags[i].textContent.trim().includes(text)) {
                return; // Tag existiert bereits
            }
        }
        
        // Neuen Tag erstellen
        const tagContainer = multiSelectInput.parentElement;
        const newTag = document.createElement('div');
        newTag.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800';
        newTag.innerHTML = `${text}<button type="button" class="ml-1 text-blue-400 hover:text-blue-600"><i class="fa-solid fa-times"></i></button>`;
        
        // Tag vor dem Input einfügen
        tagContainer.insertBefore(newTag, multiSelectInput);
        
        // Hidden Input aktualisieren
        updateMultiSelectValue();
    }
    
    // Funktion zum Aktualisieren des versteckten Input-Felds
    function updateMultiSelectValue() {
        const tags = [];
        const tagElements = multiSelectInput.parentElement.querySelectorAll('.inline-flex.items-center');
        tagElements.forEach(tag => {
            // Text ohne das "×" am Ende
            const tagText = tag.textContent.trim().replace(/\s*×\s*$/, '');
            tags.push(tagText);
        });
        multiSelectHiddenInput.value = tags.join(',');
    }
}

// Funktion zum Einrichten der Toggle-Switches
function setupToggleSwitches() {
    const toggleSwitch = document.getElementById('toggle_switch');
    const toggleValue = document.getElementById('toggle_value');
    
    if (!toggleSwitch || !toggleValue) return;
    
    toggleSwitch.addEventListener('click', function() {
        const isActive = toggleSwitch.getAttribute('aria-checked') === 'true';
        const newState = !isActive;
        
        toggleSwitch.setAttribute('aria-checked', newState.toString());
        toggleValue.value = newState ? '1' : '0';
        
        // Visuelles Feedback
        const toggleButton = toggleSwitch.querySelector('span[aria-hidden="true"]');
        if (toggleButton) {
            if (newState) {
                toggleSwitch.classList.remove('bg-gray-200');
                toggleSwitch.classList.add('bg-blue-600');
                toggleButton.classList.remove('translate-x-0');
                toggleButton.classList.add('translate-x-5');
            } else {
                toggleSwitch.classList.remove('bg-blue-600');
                toggleSwitch.classList.add('bg-gray-200');
                toggleButton.classList.remove('translate-x-5');
                toggleButton.classList.add('translate-x-0');
            }
        }
    });
}
</script>
