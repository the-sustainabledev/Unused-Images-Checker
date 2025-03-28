jQuery(function ($) {
    $('.uic-delete-btn').on('click', function () {
        const btn = $(this);
        const id = btn.data('id');
        if (!confirm('Delete this image permanently?')) return;

        btn.prop('disabled', true).text('Deleting...');

        $.post(uic_ajax.ajax_url, {
            action: 'uic_delete_image',
            id: id,
            nonce: uic_ajax.nonce
        }).done(function (response) {
            if (response.success) {
                const row = $('#uic-row-' + id);
                row.css('opacity', 0.5);
                btn.text('Deleted');
            } else {
                alert('Error: ' + response.data);
                btn.prop('disabled', false).text('Delete');
            }
        });
    });
});
