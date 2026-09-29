// @flow
import React from 'react';
import {translate} from '../../utils';
import type {ReferencingResourcesData} from '../../types';

type Props = {|
    allowDeletion: boolean,
    referencingResourcesData: ReferencingResourcesData | Array<ReferencingResourcesData>,
|};

const getResourceTypeLabel = (resourceKey: string): ?string => {
    const translationKey = 'sulu_reference.resource.' + resourceKey;
    const label = translate(translationKey);

    // resources without a translation are listed without a type instead of showing the translation key
    return label === translationKey ? undefined : label;
};

const renderReferencingResources = (referencingResourcesData: ReferencingResourcesData) => (
    <ul>
        {referencingResourcesData.referencingResources.map((item, index) => {
            const {resourceKey, title = null} = item;

            if (!title) {
                return null;
            }

            const type = getResourceTypeLabel(resourceKey);

            return (
                <li key={index}>{type ? `${title} (${type})` : title}</li>
            );
        })}
    </ul>
);

const ReferencingResources = ({allowDeletion, referencingResourcesData}: Props) => {
    if (Array.isArray(referencingResourcesData) && referencingResourcesData.length !== 1) {
        return (
            <React.Fragment>
                {allowDeletion
                    ? translate('sulu_admin.delete_linked_warning_text_multiple')
                    : translate('sulu_admin.delete_linked_abort_text')
                }

                {referencingResourcesData.map((data, index) => (
                    <React.Fragment key={index}>
                        {data.resource.title && (
                            <p>{translate('sulu_admin.delete_linked_resource_text', {title: data.resource.title})}</p>
                        )}
                        {renderReferencingResources(data)}
                    </React.Fragment>
                ))}
            </React.Fragment>
        );
    }

    const data = Array.isArray(referencingResourcesData) ? referencingResourcesData[0] : referencingResourcesData;
    const {title} = data.resource;

    return (
        <React.Fragment>
            {allowDeletion
                ? title
                    ? translate('sulu_admin.delete_linked_warning_text_with_title', {title})
                    : translate('sulu_admin.delete_linked_warning_text')
                : translate('sulu_admin.delete_linked_abort_text')
            }

            {renderReferencingResources(data)}
        </React.Fragment>
    );
};

export default ReferencingResources;
