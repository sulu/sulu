// @flow
import {listFieldTransformerRegistry} from 'sulu-admin-bundle/containers';
import CategoryKeywordsMultipleUsageTransformer
    from './containers/List/fieldTransformers/CategoryKeywordsMultipleUsageTransformer';

listFieldTransformerRegistry.add('category_keywords_multiple_usage', new CategoryKeywordsMultipleUsageTransformer());
