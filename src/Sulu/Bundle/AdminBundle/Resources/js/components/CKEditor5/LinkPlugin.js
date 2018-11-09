// @flow
import Plugin from '@ckeditor/ckeditor5-core/src/plugin';
import ButtonView from '@ckeditor/ckeditor5-ui/src/button/buttonview';
import linkIcon from '@ckeditor/ckeditor5-core/theme/icons/image.svg';

export default class LinkPlugin extends Plugin {
    init() {
        const editor = this.editor;

        editor.ui.componentFactory.add('link', (locale) => {
            const view = new ButtonView(locale);

            view.set({
                label: 'Insert link',
                icon: linkIcon,
                tooltip: true,
            });

            view.on('execute', () => {
                const url = prompt('Link URL');
                console.log('URL: ', url);
            });

            return view;
        });

        console.log('init');
    }
}
