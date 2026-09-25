// @flow
import type {ResourceViewsMap} from '../types';

class ResourceViewRegistry {
    resourceViews: ResourceViewsMap;

    constructor() {
        this.clear();
    }

    clear() {
        this.resourceViews = {};
    }

    addResourceViews(resourceViews: ResourceViewsMap) {
        Object.keys(resourceViews).forEach((resourceKey) => {
            if (this.resourceViews[resourceKey]) {
                throw new Error('The resource views for "' + resourceKey + '" has already be configured.');
            }

            this.resourceViews[resourceKey] = resourceViews[resourceKey];
        });
    }

    has(view: string, resourceKey: string): boolean {
        if (!this.resourceViews[resourceKey]) {
            return false;
        }

        const viewName = this.resourceViews?.[resourceKey]?.views?.[view];

        // placeholders like "{group}" are resolved by the ResourceViewUrlGenerator on the server only
        return viewName !== undefined && !viewName.includes('{');
    }

    get(view: string, resourceKey: string): string {
        if (typeof this.resourceViews[resourceKey] !== 'object') {
            throw new Error('The resource "' + resourceKey + '" was not found.');
        }

        if (typeof this.resourceViews[resourceKey].views[view] !== 'string') {
            throw new Error('The resource view "' + view + '" for resource "' + resourceKey + '" was not found.');
        }

        return this.resourceViews[resourceKey].views[view];
    }
}

export default new ResourceViewRegistry();
