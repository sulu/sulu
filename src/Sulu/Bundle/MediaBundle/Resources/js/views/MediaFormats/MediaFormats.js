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
import mediaFormatsStyles from './mediaFormats.scss';

const COLLECTION_ROUTE = 'sulu_media.overview';

type Props = ViewProps & {
    resourceStore: ResourceStore,
};

@observer
class MediaFormats extends React.Component<Props> {
    @observable copySuccessThumbnailKey: ?string | number;

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

    @computed get thumbnails() {
        return this.props.resourceStore.data.thumbnails;
    }

    handleDownloadClick = (id: string | number) => {
        window.open(this.thumbnails[id] + '&inline=1');
    };

    @action handleCopyClick = (id: string | number) => {
        copyToClipboard(this.thumbnails[id]);
        this.copySuccessThumbnailKey = id;
        setTimeout(action(() => this.copySuccessThumbnailKey = undefined), 500);
    };

    render() {
        const {resourceStore} = this.props;

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
                {resourceStore.loading
                    ? <Loader />
                    : <Table buttons={buttons}>
                        <Table.Header>
                            <Table.HeaderCell>{translate('sulu_media.format')}</Table.HeaderCell>
                        </Table.Header>
                        <Table.Body>
                            {Object.keys(this.thumbnails).map((thumbnailKey: string) => (
                                <Table.Row
                                    buttons={
                                        this.copySuccessThumbnailKey === thumbnailKey
                                            ? [buttons[0], {icon: 'su-check', onClick: undefined}]
                                            : buttons
                                    }
                                    id={thumbnailKey}
                                    key={thumbnailKey}
                                >
                                    <Table.Cell>{thumbnailKey}</Table.Cell>
                                </Table.Row>
                            ))}
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
