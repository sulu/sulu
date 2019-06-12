// @flow
import React from 'react';
import {action, computed, observable} from 'mobx';
import {observer} from 'mobx-react';
import copyToClipboard from 'copy-to-clipboard';
import {Loader, Table} from 'sulu-admin-bundle/components';
import {withToolbar} from 'sulu-admin-bundle/containers';
import type {ViewProps} from 'sulu-admin-bundle/containers';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import {translate} from 'sulu-admin-bundle/utils';
import formatStore from '../../stores/FormatStore';
import mediaFormatsStyles from './mediaFormats.scss';

const COLLECTION_ROUTE = 'sulu_media.overview';

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

    handleDownloadClick = (id: string | number) => {
        window.open(this.thumbnails[id] + '&inline=1');
    };

    @action handleCopyClick = (id: string | number) => {
        copyToClipboard(window.location.origin + this.thumbnails[id]);
        this.copySuccessThumbnailKey = id;
        setTimeout(action(() => this.copySuccessThumbnailKey = undefined), 500);
    };

    render() {
        const {formats} = this;
        const {resourceStore, title} = this.props;

        const buttons = [
            {
                icon: 'su-eye',
                onClick: this.handleDownloadClick,
            },
            {
                icon: 'su-copy',
                onClick: this.handleCopyClick,
            },
        ];

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
                            {formats
                                .filter((format) => !format.internal)
                                .map((format: Object) => (
                                    <Table.Row
                                        buttons={
                                            this.copySuccessThumbnailKey === format.key
                                                ? [buttons[0], {icon: 'su-check', onClick: undefined}]
                                                : buttons
                                        }
                                        id={format.key}
                                        key={format.key}
                                    >
                                        <Table.Cell>{format.title}</Table.Cell>
                                        <Table.Cell>{format.key}</Table.Cell>
                                    </Table.Row>
                                ))
                            }
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
                resourceStore.setLocale(locale);
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
