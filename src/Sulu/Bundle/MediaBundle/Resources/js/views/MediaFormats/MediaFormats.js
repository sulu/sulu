// @flow
import React from 'react';
import {action, computed, observable} from 'mobx';
import {observer} from 'mobx-react';
import copyToClipboard from 'copy-to-clipboard';
import {Loader, Table} from 'sulu-admin-bundle/components';
import {withToolbar} from 'sulu-admin-bundle/containers';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import {translate} from 'sulu-admin-bundle/utils';
import formatStore from '../../stores/formatStore';
import mediaFormatsStyles from './mediaFormats.scss';
import type {ViewProps} from 'sulu-admin-bundle/containers';

const COLLECTION_ROUTE = 'sulu_media.overview';
const MASTER_FILE_ID = '__master_file__';

function appendQueryParameter(url: string, parameter: string): string {
    return url + (url.includes('?') ? '&' : '?') + parameter;
}

type Props = ViewProps & {
    resourceStore: ResourceStore,
    title?: string,
};

@observer
class MediaFormats extends React.Component<Props> {
    @observable copySuccessThumbnailKey: ?string | number;
    @observable formats: ?Array<Object>;

    constructor(props: Props) {
        super(props);

        const {
            router,
            resourceStore,
        } = this.props;

        const locale = resourceStore.locale;

        if (!locale) {
            throw new Error('The resourceStore for the MediaFormats must have a locale');
        }

        router.bind('locale', locale);
    }

    componentDidMount() {
        formatStore.loadFormats().then(action((formats) => {
            this.formats = formats;
        }));
    }

    @computed get thumbnails() {
        return this.props.resourceStore.data.thumbnails;
    }

    @computed get masterFileUrl(): ?string {
        const {adminUrl, url} = this.props.resourceStore.data;

        return adminUrl || url;
    }

    getUrl(id: string | number): string {
        return (id === MASTER_FILE_ID ? this.masterFileUrl : this.thumbnails[id]) || '';
    }

    handleOpenClick = (id: string | number) => {
        window.open(appendQueryParameter(this.getUrl(id), 'inline=1'));
    };

    handleDownloadClick = (id: string | number) => {
        const link = document.createElement('a');
        link.href = appendQueryParameter(this.getUrl(id), 'inline=0');
        link.download = '';
        link.click();
    };

    @action handleCopyClick = (id: string | number) => {
        copyToClipboard(window.location.origin + this.getUrl(id));
        this.copySuccessThumbnailKey = id;
        setTimeout(action(() => this.copySuccessThumbnailKey = undefined), 500);
    };

    render() {
        const {formats} = this;
        const {resourceStore, title} = this.props;

        const buttons = [
            {
                icon: 'su-eye',
                onClick: this.handleOpenClick,
            },
            {
                icon: 'su-download',
                onClick: this.handleDownloadClick,
            },
            {
                icon: 'su-copy',
                onClick: this.handleCopyClick,
            },
        ];
        const getRowButtons = (id) => this.copySuccessThumbnailKey === id
            ? [buttons[0], buttons[1], {icon: 'su-check', onClick: undefined}]
            : buttons;
        const rows = [];

        if (this.masterFileUrl) {
            rows.push(
                <Table.Row buttons={getRowButtons(MASTER_FILE_ID)} id={MASTER_FILE_ID} key={MASTER_FILE_ID}>
                    <Table.Cell>{translate('sulu_media.master_file')}</Table.Cell>
                    <Table.Cell>{resourceStore.data.name}</Table.Cell>
                </Table.Row>
            );
        }

        (formats || [])
            .filter((format) => !format.internal)
            .forEach((format: Object) => rows.push(
                <Table.Row buttons={getRowButtons(format.key)} id={format.key} key={format.key}>
                    <Table.Cell>{format.title}</Table.Cell>
                    <Table.Cell>{format.key}</Table.Cell>
                </Table.Row>
            ));

        return (
            <div className={mediaFormatsStyles.mediaFormats}>
                {title && <h1>{title}</h1>}
                {resourceStore.loading || !formats
                    ? <Loader />
                    : <Table buttons={buttons}>
                        <Table.Header>
                            <Table.HeaderCell>{translate('sulu_admin.title')}</Table.HeaderCell>
                            <Table.HeaderCell>{translate('sulu_admin.key')}</Table.HeaderCell>
                        </Table.Header>
                        <Table.Body>
                            {rows}
                        </Table.Body>
                    </Table>
                }
            </div>
        );
    }
}

export default withToolbar(MediaFormats, function() {
    const {resourceStore, router} = this.props;
    const {locales} = router.route.options;
    const locale = locales
        ? {
            value: resourceStore.locale.get(),
            onChange: (locale) => {
                router.navigate(router.route.name, {...router.attributes, locale});
            },
            options: locales.map((locale) => ({
                value: locale,
                label: locale,
            })),
        }
        : undefined;

    return {
        locale,
        backButton: {
            onClick: () => {
                router.restore(COLLECTION_ROUTE, {locale: resourceStore.locale.get()});
            },
        },
    };
});
