// @flow
import React from 'react';
import {translate} from '../../utils';
import styles from './referencingResources.scss';
import type {Node} from 'react';
import type {ReferencingResource, ReferencingResourcesData} from '../../types';

type Props = {|
    allowDeletion: boolean,
    referencingResourcesData: ReferencingResourcesData | Array<ReferencingResourcesData>,
|};

const MAX_TITLE_LENGTH = 60;

// a title without spaces would make the dialog scroll sideways
const truncateTitle = (title: string): string => {
    const characters = Array.from(title);

    return characters.length > MAX_TITLE_LENGTH
        ? characters.slice(0, MAX_TITLE_LENGTH - 1).join('') + '…'
        : title;
};

const getResourceTypeLabel = (resourceKey: string): ?string => {
    const translationKey = 'sulu_reference.resource.' + resourceKey;
    const label = translate(translationKey);

    return label === translationKey ? undefined : label;
};

const renderReferencingResource = ({resourceKey, title = null, url = null}: ReferencingResource): Node => {
    const type = getResourceTypeLabel(resourceKey);
    // the dialog stays open behind the new tab, so the pending delete is not lost
    const name = url
        ? <a className={styles.link} href={url} rel="noopener noreferrer" target="_blank">{title}</a>
        : title;

    return type ? <React.Fragment>{name} ({type})</React.Fragment> : name;
};

const getReferencingResourceItems = (referencingResourcesData: ReferencingResourcesData): Array<Node> => {
    return referencingResourcesData.referencingResources
        .filter(({title}) => !!title)
        .map(renderReferencingResource);
};

// the response lists only the first resources, but counts all of them
const getHiddenCount = ({referencingResources, referencingResourcesCount}: ReferencingResourcesData): number => {
    return Math.max(0, referencingResourcesCount - referencingResources.length);
};

const renderItems = (items: Array<Node>, hiddenCount: number) => (
    <ul>
        {items.map((item, index) => <li key={index}>{item}</li>)}
        {hiddenCount > 0 && <li>{translate('sulu_admin.delete_linked_more_text', {count: hiddenCount})}</li>}
    </ul>
);

const renderResourceHeading = (title: string) => {
    const shownTitle = truncateTitle(title);
    const text = translate('sulu_admin.delete_linked_resource_text', {title: shownTitle});
    const titleIndex = text.indexOf(shownTitle);

    if (titleIndex === -1) {
        return text;
    }

    const textAfterTitle = text.slice(titleIndex + shownTitle.length);
    // the closing quote stays with the title
    const [, closing, rest] = textAfterTitle.match(/^(\S*)\s+([\s\S]*)$/) || [undefined, textAfterTitle, ''];

    return (
        <React.Fragment>
            <span className={styles.title}>{text.slice(0, titleIndex) + shownTitle + closing}</span>
            {rest && ' '}
            {rest && <span className={styles.rest}>{rest}</span>}
        </React.Fragment>
    );
};

const ReferencingResources = ({allowDeletion, referencingResourcesData}: Props) => {
    if (Array.isArray(referencingResourcesData) && referencingResourcesData.length !== 1) {
        return (
            <div className={styles.multiple}>
                {allowDeletion
                    ? translate('sulu_admin.delete_linked_warning_text_multiple')
                    : translate('sulu_admin.delete_linked_abort_text')
                }

                <div className={styles.items}>
                    {referencingResourcesData.map((data, index) => {
                        const {title} = data.resource;
                        const items = getReferencingResourceItems(data);
                        const hiddenCount = getHiddenCount(data);
                        const inline = !!title && items.length === 1 && hiddenCount === 0;

                        return (
                            <React.Fragment key={index}>
                                {title && (
                                    <p>
                                        {renderResourceHeading(title)}
                                        {inline && <span className={styles.inline}>{' '}{items[0]}</span>}
                                    </p>
                                )}
                                {!inline && renderItems(items, hiddenCount)}
                            </React.Fragment>
                        );
                    })}
                </div>
            </div>
        );
    }

    const data = Array.isArray(referencingResourcesData) ? referencingResourcesData[0] : referencingResourcesData;
    const {title} = data.resource;

    return (
        <React.Fragment>
            {allowDeletion
                ? title
                    ? translate('sulu_admin.delete_linked_warning_text_with_title', {title: truncateTitle(title)})
                    : translate('sulu_admin.delete_linked_warning_text')
                : translate('sulu_admin.delete_linked_abort_text')
            }

            {renderItems(getReferencingResourceItems(data), getHiddenCount(data))}
        </React.Fragment>
    );
};

export default ReferencingResources;
