// @flow
import userStore from './userStore';
import type {HandleResponseHook} from '../../services/Requester/types';

const logoutOnUnauthorizedResponse: HandleResponseHook = function(response: Response) {
    if (response.status === 401) {
        userStore.setLoggedIn(false);
    }
};

export default logoutOnUnauthorizedResponse;
