// @flow
import {AbstractFormToolbarAction} from 'sulu-admin-bundle/views';
import {translate} from 'sulu-admin-bundle/utils';
import downloadOriginalFile from '../../../utils/downloadOriginalFile';

export default class DownloadOriginalToolbarAction extends AbstractFormToolbarAction {
    getToolbarItemConfig() {
        const {adminUrl, url} = this.resourceFormStore.data;

        return {
            disabled: !adminUrl && !url,
            icon: 'su-download',
            label: translate('sulu_media.download_masterfile'),
            onClick: () => downloadOriginalFile(this.resourceFormStore.data),
            type: 'button',
        };
    }
}
