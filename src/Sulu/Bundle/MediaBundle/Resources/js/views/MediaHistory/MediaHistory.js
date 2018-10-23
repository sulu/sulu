// @flow
import React from 'react';
import {computed} from 'mobx';
import {observer} from 'mobx-react';
import {Loader, Table} from 'sulu-admin-bundle/components';
import {withToolbar} from 'sulu-admin-bundle/containers';
import type {ViewProps} from 'sulu-admin-bundle/containers';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import {translate} from 'sulu-admin-bundle/utils';
import mediaHistoryStyles from './mediaHistory.scss';

const COLLECTION_ROUTE = 'sulu_media.overview';

type Props = ViewProps & {
    resourceStore: ResourceStore,
};

@observer
class MediaHistory extends React.Component<Props> {
    constructor(props: Props) {
        super(props);

        const {
            router,
            resourceStore,
        } = this.props;

        const locale = resourceStore.locale;

        if (!locale) {
            throw new Error('The resourceStore for the MediaHistory must have a locale');
        }

        router.bind('locale', locale);
    }

    @computed get versions(): Array<Object> {
        // $FlowFixMe
        return Object.values(this.props.resourceStore.data.versions);
    }

    handleShowClick = (id: string | number) => {
        const version = this.versions.find((version) => version.version === id);
        if (!version) {
            throw new Error('Version "' + id + '" was not found. This should not happen and is likely a bug.');
        }

        window.open(version.url + '&inline=1');
    };

    render() {
        const {resourceStore} = this.props;

        const buttons = [
            {
                icon: 'su-eye',
                onClick: this.handleShowClick,
            },
        ];

        return (
            <div className={mediaHistoryStyles.mediaHistory}>
                {resourceStore.loading
                    ? <Loader />
                    : <Table buttons={buttons}>
                        <Table.Header>
                            <Table.HeaderCell>{translate('sulu_media.version')}</Table.HeaderCell>
                            <Table.HeaderCell>{translate('sulu_admin.created')}</Table.HeaderCell>
                        </Table.Header>
                        <Table.Body>
                            {this.versions.reverse().map((version: Object) => (
                                <Table.Row id={version.version} key={version.version}>
                                    <Table.Cell>{translate('sulu_media.version')} {version.version}</Table.Cell>
                                    <Table.Cell>{(new Date(version.created)).toLocaleString()}</Table.Cell>
                                </Table.Row>
                            ))}
                        </Table.Body>
                    </Table>
                }
            </div>
        );
    }
}

export default withToolbar(MediaHistory, function() {
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
