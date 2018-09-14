// @flow
import {bundleReady} from 'sulu-admin-bundle/services';
import {fieldRegistry} from 'sulu-admin-bundle/containers';
import Permissions from './containers/Form/fields/Permissions';

fieldRegistry.add('permissions', Permissions);

bundleReady();
