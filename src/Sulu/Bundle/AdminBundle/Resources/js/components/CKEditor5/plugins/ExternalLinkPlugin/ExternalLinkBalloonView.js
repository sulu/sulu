// @flow
import ButtonView from '@ckeditor/ckeditor5-ui/src/button/buttonview';
import View from '@ckeditor/ckeditor5-ui/src/view';
// $FlowFixMe
import editIcon from '!!raw-loader!./edit.svg'; // eslint-disable-line import/no-webpack-loader-syntax
// $FlowFixMe
import unlinkIcon from '!!raw-loader!./unlink.svg'; // eslint-disable-line import/no-webpack-loader-syntax

export default class ExternalLinkBalloonView extends View {
    constructor(locale: string) {
        super(locale);

        const previewButtonView = new ButtonView(this.locale);

        previewButtonView.set({
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

        const editButtonView = new ButtonView(this.locale);
        editButtonView.set({
            icon: editIcon,
        });

        const unlinkButtonView = new ButtonView(this.locale);
        unlinkButtonView.set({
            icon: unlinkIcon,
        });

        editButtonView.delegate('execute').to(this, 'externalLink');
        unlinkButtonView.delegate('execute').to(this, 'externalUnlink');

        this.setTemplate({
            tag: 'div',
            children: [
                previewButtonView,
                editButtonView,
                unlinkButtonView,
            ],
        });
    }
}
