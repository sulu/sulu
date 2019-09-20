// @flow
import {initializer} from 'sulu-admin-bundle/services';
import {fieldRegistry} from 'sulu-admin-bundle/containers';
import {Location} from './containers/Form';

initializer.addUpdateConfigHook('sulu_location', (config: Object) => {
    fieldRegistry.add('location', Location, config);
});
