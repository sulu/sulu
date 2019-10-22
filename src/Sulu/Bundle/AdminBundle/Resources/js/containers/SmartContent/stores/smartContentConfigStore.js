// @flow
import type {FilterCriteria, Presentation, SmartContentConfigs} from '../types';

class SmartContentConfigStore {
    config: SmartContentConfigs;

    clear() {
        this.config = {};
    }

    setConfig(config: SmartContentConfigs) {
        this.config = config;
    }

    getConfig(provider: string) {
        return this.config[provider];
    }

    getDefaultValue(provider: string, presentations: Array<Presentation>): FilterCriteria {
        const config = this.getConfig(provider);

        return {
            audienceTargeting: config.audienceTargeting ? false : undefined,
            categories: undefined,
            categoryOperator: config.categories ? 'or' : undefined,
            dataSource: undefined,
            includeSubFolders: config.datasourceResourceKey ? false : undefined,
            limitResult: undefined,
            presentAs: presentations.length > 0 ? presentations[0].name : undefined,
            sortBy: config.sorting.length > 0 ? config.sorting[0].name : undefined,
            sortMethod: config.sorting.length > 0 ? 'asc' : undefined,
            tagOperator: config.tags ? 'or' : undefined,
            tags: undefined,
        };
    }
}

export default new SmartContentConfigStore();
