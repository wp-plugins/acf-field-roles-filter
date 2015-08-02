(function($){
    var acf_ids = [];

    setTimeout(updateIDs, 2000);

    function updateIDs() {
        acf_ids = [];
        $fields = $('#acf_fields .fields div.field');
        $('#acf_fields .fields div.field').each( function(){
            var id = $(this).data('id');
            if( id == 'field_clone' ) {
                return;
            }

            acf_ids.push(id);
        } );
    }

    function addField() {
        if($(this).closest('.repeater').length != 0) {
            return;
        }

        var temp_ids = acf_ids;
        updateIDs();

        var id;

        $.each( acf_ids, function(index, value){
            if( temp_ids.indexOf(value) != -1 ) {
                return;
            }
            id = value;
            return false;
        });

        var label = $('#acf_fields div.field[data-id="'+id+'"] input.label').val();

        var div = '<tr data-id="'+id+'"><td class="label"><label class="label">'+label+'</label></td>';
        div += '<td><ul class="acf-checkbox-list checkbox horizontal">';

        $.each( roles, function(index, value){
            div += '<li><label><input checked="checked" type="checkbox" class="checkbox" name="roles['+id+'][]" value="'+index+'">'+value+'</label></li>';
        });

        div += '</ul></td></tr>';

        $('#acf_roles tbody').append(div);
    }

    $(document).on('click', '#acf_fields a.acf_duplicate_field', addField);

    $(document).on('click', '#acf_fields #add_field', addField);

    $(document).on('click', '#acf_fields a.acf_delete_field', function(){
        if($(this).closest('.repeater').length != 0) {
            return;
        }

        setTimeout(function(){
            var temp_ids = acf_ids;
            updateIDs();

            var id;

            $.each( temp_ids, function(index, value){
                if( acf_ids.indexOf(value) != -1 ) {
                    return;
                }
                id = value;
                return false;
            });

            $('#acf_roles tbody tr[data-id="'+id+'"]').fadeOut( 600, function() {
                $(this).remove();
            });
        }, 1000);
    });

    $(document).on('change', '#acf_fields tr.field_label input.label', function() {
        if($(this).closest('.repeater').length != 0) {
            return;
        }

        var label = $(this).val();
        var id = $(this).attr('name').match(/(field_[a-zA-Z0-9]*)/g);

        $('#acf_roles tr[data-id="'+id.pop()+'"] td.label label').text(label);
    });
})(jQuery);