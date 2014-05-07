(function() {

    // plugin not finished!!!

    CKEDITOR.plugins.add('fontsize', {
        // lang: TODO
        icons: 'decreasefontsize,increasefontsize',
        hidpi: false,

        init: function(editor) {

            editor.addCommand('IncreaseFontsize', this.increaseFontsize);
            editor.ui.addButton('increasefontsize', {
                label: 'Increase Fontsize',
                command: 'IncreaseFontsize',
                toolbar: 'styles'
            });

            editor.addCommand('DecreaseFontsize', this.increaseFontsize);
            editor.ui.addButton('decreasefontsize', {
                label: 'Decrease Fontsize',
                command: 'DecreaseFontsize',
                toolbar: 'styles'
            });
        },

        /**
         * Command to increase fontsize
         * @param editor
         * @param operation
         * @returns {{exec: exec}}
         */
        increaseFontsize: {

            exec: function(editor) {

                var selectedText = editor.getSelection().getSelectedText(),
                    $span = new CKEDITOR.dom.element("span");

                if (!!editor && !!selectedText) {

                    $span.setAttributes({class: 'myClass'});
                    $span.setText(selectedText);
                    editor.insertElement($span);

                } else {
                    return;
                }
            }

        },

        /**
         * Command to decrease fontsize
         * @param editor
         * @param operation
         * @returns {{exec: exec}}
         */
        decreaseFontsize: {

            exec: function(editor) {

                var selection = editor.getSelection().getSelectedText();

                if (!!editor && !!selection) {

                } else {
                    return;
                }
            }

        }

    });
})();
