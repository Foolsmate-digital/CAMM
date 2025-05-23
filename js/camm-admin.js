jQuery(document).ready(function($){
    // Drag & Drop
    $('#camm-menu-list').sortable({
        placeholder: 'ui-state-highlight',
        axis: 'y',
        handle: '.camm-handle',
        update: function(event, ui) {
            // Reihenfolge der hidden inputs anpassen
            $('#camm-menu-list li').each(function(i, el){
                $(el).find('input.camm-order').attr('name', $(el).data('order-name') + '['+i+']');
            });
        }
    });
    $('#camm-menu-list').disableSelection();

    // Switches: Toggle optisch und Wert
    $('.camm-switch input[type="checkbox"]').on('change', function(){
        // Optional: Custom logic
    });

    // Dropdowns: Placeholder für spätere Optionen
    $('.camm-dropdown').on('click', function(){
        // Optional: Dropdown-Logik
    });

    // Mediathek-Integration (Platzhalter)
    $('.camm-icon-upload').on('click', function(e){
        e.preventDefault();
        // Hier später: wp.media Dialog öffnen
        alert('Mediathek-Integration folgt!');
    });

    // Untermenüs auf- und zuklappen
    $('#camm-menu-list').on('click', '.camm-submenu-toggle', function(){
        var $btn = $(this);
        var $li = $btn.closest('li');
        var $submenu = $li.children('.camm-submenu-list');
        var expanded = $btn.attr('aria-expanded') === 'true';
        $submenu.slideToggle(150);
        $btn.attr('aria-expanded', expanded ? 'false' : 'true');
        $btn.find('.dashicons').toggleClass('dashicons-arrow-down dashicons-arrow-up');
    });
}); 