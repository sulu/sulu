// @flow
import logoutOnUnauthorizedResponse from '../logoutOnUnauthorizedResponse';
import userStore from '../UserStore';

jest.mock('../UserStore', () => ({
    setLoggedIn: jest.fn(),
}));

test('Call setLoggedIn in userStore to false when response has status 401', () => {
    // $FlowFixMe
    const response: Response = {status: 401};

    logoutOnUnauthorizedResponse(response);

    expect(userStore.setLoggedIn).toBeCalledWith(false);
});

test('Do not call setLoggedIn in userStore when response has not status 401', () => {
    // $FlowFixMe
    const response: Response = {status: 200};

    logoutOnUnauthorizedResponse(response);

    expect(userStore.setLoggedIn).not.toBeCalled();
});
