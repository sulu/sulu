// @flow
import {AbstractListItemAction} from 'sulu-admin-bundle/views';

/**
 * @experimental
 */
export default class ShowVersionItemAction extends AbstractListItemAction {
    handleShowClick = (url: string) => {
        window.open(url + '&inline=1');
    };

    getItemActionConfig(item: ?Object) {
        const url = item?.adminUrl || item?.url;

        return {
            icon: 'su-eye',
            onClick: url ? () => this.handleShowClick(url) : undefined,
            disabled: !url,
        };
    }
}
