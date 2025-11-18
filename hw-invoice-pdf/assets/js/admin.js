(function($){
    function initMediaControl($control){
        $control.on('click', '.hwip-upload-media', function(e){
            e.preventDefault();
            var targetField = $('#' + $control.data('target'));
            var frame = wp.media({
                title: $(this).data('title') || (HWIPAdmin && HWIPAdmin.chooseImage),
                button: { text: HWIPAdmin && HWIPAdmin.useImage ? HWIPAdmin.useImage : 'Use image' },
                multiple: false
            });
            frame.on('select', function(){
                var attachment = frame.state().get('selection').first().toJSON();
                targetField.val(attachment.id);
                $control.find('.hwip-media-preview').attr('src', attachment.url).show();
            });
            frame.open();
        });

        $control.on('click', '.hwip-remove-media', function(e){
            e.preventDefault();
            $('#' + $control.data('target')).val('');
            $control.find('.hwip-media-preview').hide().attr('src', '');
        });
    }

    $(function(){
        $('.hwip-media-control').each(function(){
            initMediaControl($(this));
        });
        if ( $.fn.wpColorPicker ) {
            $('.hwip-color-picker').wpColorPicker();
        }
    });
})(jQuery);
