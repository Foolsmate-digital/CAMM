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
    // Entferne Dropdown-Logik für .camm-dropdown, solange keine Optionen hinterlegt sind
    // $('.camm-dropdown').on('click', function(){ });

    // Mediathek-Integration für Icon-Upload
    $('.camm-icon-upload').on('click', function(e){
        e.preventDefault();
        var $wrap = $(this).closest('.camm-icon-wrap');
        var $preview = $wrap.find('.camm-icon-preview');
        var $urlField = $wrap.find('.camm-icon-url');
        var $colorField = $wrap.find('.camm-icon-color');
        var $colorWrap = $wrap.find('.camm-icon-colorpicker-wrap');
        var $colorPicker = $wrap.find('.camm-icon-colorpicker');
        // Open WP Media
        var frame = wp.media({
            title: 'Icon auswählen',
            button: { text: 'Icon übernehmen' },
            multiple: false,
            library: { type: ['image/svg+xml', 'image/png', 'image/jpeg', 'image/gif'] }
        });
        frame.on('select', function(){
            var attachment = frame.state().get('selection').first().toJSON();
            $urlField.val(attachment.url);
            if ($preview.length) {
                $preview.attr('src', attachment.url).show();
            } else {
                $wrap.prepend('<img class="camm-icon camm-icon-preview" src="'+attachment.url+'" alt="Icon">');
            }
            // SVG?
            if (attachment.url.match(/\.svg$/i)) {
                $colorWrap.show();
                // Versuche SVG zu laden und Farbe zu setzen
                $.get(attachment.url, function(data) {
                    var svg = $(data).find('svg');
                    var color = $colorPicker.val();
                    svg.attr('fill', color);
                    $preview.replaceWith(svg.addClass('camm-icon camm-icon-preview'));
                }, 'xml');
            } else {
                $colorWrap.hide();
            }
        });
        frame.open();
    });
    // SVG-Farbe global setzen
    $('.camm-icon-colorpicker').on('input', function(){
        var color = $(this).val();
        $('.camm-icon-color').val(color);
        // Alle SVG-Previews einfärben
        $('.camm-icon-preview').each(function(){
            if ($(this).is('svg')) {
                $(this).attr('fill', color);
            }
        });
    });

    // Untermenüs auf- und zuklappen (Akkordeon)
    $('#camm-menu-list').on('click', '.camm-submenu-toggle', function(){
        var $btn = $(this);
        var $li = $btn.closest('li');
        var $submenu = $li.children('.camm-submenu-list');
        var expanded = $btn.attr('aria-expanded') === 'true';
        // Akkordeon: schließe alle anderen
        $('#camm-menu-list .camm-submenu-list').not($submenu).slideUp(150);
        $('#camm-menu-list .camm-submenu-toggle').not($btn).attr('aria-expanded', 'false').find('.dashicons').removeClass('dashicons-arrow-up').addClass('dashicons-arrow-down');
        if (!expanded) {
            $submenu.slideDown(180);
            $btn.attr('aria-expanded', 'true');
            $btn.find('.dashicons').removeClass('dashicons-arrow-down').addClass('dashicons-arrow-up');
        } else {
            $submenu.slideUp(150);
            $btn.attr('aria-expanded', 'false');
            $btn.find('.dashicons').removeClass('dashicons-arrow-up').addClass('dashicons-arrow-down');
        }
    });
}); 