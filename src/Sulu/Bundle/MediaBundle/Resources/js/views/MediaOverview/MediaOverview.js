// @flow
import React from 'react';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import {translate} from 'sulu-admin-bundle/services';
import {withToolbar} from 'sulu-admin-bundle/containers';
import type {ViewProps} from 'sulu-admin-bundle/containers';
import {Masonry} from 'sulu-admin-bundle/components';
import MediaCard from '../../components/MediaCard';

@observer
class MediaOverview extends React.PureComponent<ViewProps> {
    medias: Array<*> = [
        { id: 1, size: '260/350', title: 'This is a boring title boring fin fjfjd djdjd', meta: 'bo and ring' },
        { id: 2, size: '260/260', title: 'Is this one better?', meta: 'No' },
        { id: 3, size: '260/300', title: 'But now!', meta: 'Hmm, not sure' },
        { id: 4, size: '260/260', title: 'You want to have a fight?', meta: 'Come at me!' },
        { id: 5, size: '260/380', title: 'LOL', meta: 'Yea, I thought so' },
        { id: 6, size: '260/200', title: 'Now back to the Masonry', meta: ':)' },
        { id: 7, size: '260/400', title: 'This is an image', meta: 'You are so smart' },
        { id: 8, size: '260/180', title: 'This image has meta info', meta: 'No' },
        { id: 9, size: '260/250', title: 'Dude, cmon', meta: 'NO' },
        { id: 10, size: '260/200', title: 'Pls, you are embarrassing me', meta: 'Ugh, ok' },
        { id: 11, size: '260/150', title: 'An image', meta: 'image/png, 3,2 MB' },
    ];

    @observable selectedMediaIds: Array<string | number> = [];

    @action handleMediaCardSelectionChange = (id: string | number, selected: boolean) => {
        if (selected) {
            this.selectedMediaIds.push(id);
        } else {
            this.selectedMediaIds = this.selectedMediaIds.filter((selectedId) => selectedId !== id);
        }
    };

    isSelected = (id: string | number) => {
        return this.selectedMediaIds.includes(id);
    };

    render() {
        const selectedCount = this.selectedMediaIds.length;

        return (
            <div>
                <h1>Media Overview</h1>
                {!!selectedCount &&
                    <p>{selectedCount} Items selected</p>
                }
                <Masonry>
                    {
                        this.medias.map((media) => (
                            <MediaCard
                                id={media.id}
                                key={media.id}
                                title={media.title}
                                meta={media.meta}
                                icon="pencil"
                                selected={this.isSelected(media.id)}
                                onSelectionChange={this.handleMediaCardSelectionChange}
                                imageURL={`http://lorempixel.com/${media.size}`}
                            />
                        ))
                    }
                </Masonry>
            </div>
        );
    }
}

export default withToolbar(MediaOverview, function() {
    return {
        items: [
            {
                type: 'button',
                value: translate('sulu_admin.add'),
                icon: 'plus-circle',
                onClick: () => {},
            },
            {
                type: 'button',
                value: translate('sulu_admin.delete'),
                icon: 'trash-o',
                onClick: () => {},
            },
        ],
    };
});
