// @flow
import AbstractListItemAction from './AbstractListItemAction';

export default class LinkItemAction extends AbstractListItemAction {
    handleDownloadClick = (linkUrl: string) => {
        window.location.href = linkUrl;
    };

    getItemActionConfig(item: ?Object) {
        const {
            icon = 'su-link',
            link_property: linkProperty,
        } = this.options;

        if (typeof icon !== 'string') {
            throw new Error('The "icon" must have a string value!');
        }

        if (typeof linkProperty !== 'string') {
            throw new Error('The "link_property" option cannot be null and must have a string value!');
        }

        const linkValue = item ? item[linkProperty] : null;
        if (linkValue && linkProperty !== 'string') {
            throw new Error('The value of the property given via  "link_property" must have a string value!');
        }

        return {
            icon,
            onClick: linkValue ? () => this.handleDownloadClick(linkValue) : null,
            disabled: !linkValue,
        };
    }
}
