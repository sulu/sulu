// @flow
import {listItemActionRegistry} from 'sulu-admin-bundle/views';
import RestoreItemAction from './views/List/itemActions/RestoreItemAction';

listItemActionRegistry.add('sulu_trash.restore', RestoreItemAction);
