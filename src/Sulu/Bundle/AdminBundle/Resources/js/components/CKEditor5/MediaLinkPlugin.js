// @flow
import * as React from 'react';
import Plugin from '@ckeditor/ckeditor5-core/src/plugin';
import ButtonView from '@ckeditor/ckeditor5-ui/src/button/buttonview';
import mediaLinkIcon from '@ckeditor/ckeditor5-core/theme/icons/image.svg';

export default (
    renderComponent: (component: React.ReactElement) => void
) => {

    const MyComponent = (props) => {
        return <h1>Hello</h1>;
    };

    return class MediaLinlPlugin extends Plugin {
        init() {
            const editor = this.editor;

            editor.ui.componentFactory.add('mediaLink', (locale) => {
                const view = new ButtonView(locale);

                view.set({
                    label: 'Insert media link',
                    icon: mediaLinkIcon,
                    tooltip: true,
                });

                view.on('execute', () => {
                    console.log("test");
                    renderComponent(MyComponent);
                });

                return view;
            });
        }
    };
}
