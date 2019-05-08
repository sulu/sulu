// @flow
import ButtonView from '@ckeditor/ckeditor5-ui/src/button/buttonview';
import View from '@ckeditor/ckeditor5-ui/src/view';
// $FlowFixMe
import editIcon from './edit.svg'; // eslint-disable-line import/no-webpack-loader-syntax
// $FlowFixMe
import unlinkIcon from './unlink.svg'; // eslint-disable-line import/no-webpack-loader-syntax

export default class LinkBalloonView extends View {
    constructor(locale: string, hasPreview: boolean = false) {
        super(locale);

        const children = [];

        if (hasPreview) {
            const previewButtonView = new ButtonView(this.locale);

            previewButtonView.set({
                class: 'ck-preview-button',
                withText: true,
            });

            previewButtonView.extendTemplate({
                attributes: {
                    href: this.bindTemplate.to('href'),
                    target: '_blank',
                },
            });

            previewButtonView.bind('label').to(this, 'href');
            previewButtonView.template.tag = 'a';
            previewButtonView.template.eventListeners = {};

            children.push(previewButtonView);
        }

        const editButtonView = new ButtonView(this.locale);
        editButtonView.set({icon: editIcon});
        editButtonView.delegate('execute').to(this, 'link');
        children.push(editButtonView);

        const unlinkButtonView = new ButtonView(this.locale);
        unlinkButtonView.set({icon: unlinkIcon});
        unlinkButtonView.delegate('execute').to(this, 'unlink');
        children.push(unlinkButtonView);

        this.setTemplate({
            tag: 'div',
            children,
        });
    }
}
