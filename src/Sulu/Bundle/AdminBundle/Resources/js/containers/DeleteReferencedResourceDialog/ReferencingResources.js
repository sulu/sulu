// @flow
import React from 'react';
import {translate} from '../../utils';
import styles from './referencingResources.scss';
import type {ReferencingResourcesData} from '../../types';

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

const getReferencingResourceLabels = (referencingResourcesData: ReferencingResourcesData): Array<string> => {
    return referencingResourcesData.referencingResources.flatMap(({resourceKey, title = null}) => {
        if (!title) {
            return [];
        }

        const type = getResourceTypeLabel(resourceKey);

        return [type ? `${title} (${type})` : title];
    });
};

const renderLabels = (labels: Array<string>) => (
    <ul>
        {labels.map((label, index) => <li key={index}>{label}</li>)}
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
                        const labels = getReferencingResourceLabels(data);
                        const inline = !!title && labels.length === 1;

                        return (
                            <React.Fragment key={index}>
                                {title && (
                                    <p>
                                        {renderResourceHeading(title)}
                                        {inline && <span className={styles.inline}>{' ' + labels[0]}</span>}
                                    </p>
                                )}
                                {!inline && renderLabels(labels)}
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

            {renderLabels(getReferencingResourceLabels(data))}
        </React.Fragment>
    );
};

export default ReferencingResources;
