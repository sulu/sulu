// @flow
import {toJS} from 'mobx';
import userStore from '../../../stores/userStore';

export default function(): {[string]: any} {
    return {__user: toJS(userStore.user)};
}
