// @flow
import {render} from '@testing-library/react';
import {observable} from 'mobx';
import ListStore from '../../../../containers/List/stores/ListStore';
import Router from '../../../../services/Router';
import List from '../../../../views/List';
import {ResourceRequester} from '../../../../services';
import WorkflowTransitionRequestReviewOverlay
    from '../../../Form/components/WorkflowTransitionRequestReviewOverlay';
import ReviewWorkflowTransitionRequestItemAction
    from '../../itemActions/ReviewWorkflowTransitionRequestItemAction';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('sulu-admin-bundle/services/ResourceRequester', () => ({
    get: jest.fn(),
}));

jest.mock('sulu-admin-bundle/containers/List/stores/ListStore', () => jest.fn(function(resourceKey) {
    this.resourceKey = resourceKey;
}));

jest.mock('sulu-admin-bundle/views/List/List', () => jest.fn());

jest.mock('sulu-admin-bundle/services/Router', () => jest.fn(function() {
    this.attributes = {};
    this.navigate = jest.fn();
}));

// Mocked so the overlay's own rendering stays out of this test: what matters here is the props the
// item action hands it.
jest.mock('../../../Form/components/WorkflowTransitionRequestReviewOverlay', () => jest.fn(() => null));

// The module is mocked above, so flow still sees the component's own type rather than the jest mock.
const overlayMock: any = WorkflowTransitionRequestReviewOverlay;

const request = {
    approvalProgress: {approved: 1, rejected: 0, required: 1},
    createdBy: {fullName: 'Anna Berger', id: 1},
    id: 'request-1',
    locale: 'en',
    requestedAt: '2026-06-25T14:30:00+00:00',
    resourceId: '5',
    resourceKey: 'pages',
    approvals: [],
    checks: [],
    status: 'published',
};

function createItemAction() {
    const router = new Router({});
    const listStore = new ListStore(
        'workflow_transition_requests',
        'workflow_transition_requests',
        'settings-key',
        {page: observable.box(1)}
    );
    const list = new List({route: router.route, router});

    return new ReviewWorkflowTransitionRequestItemAction(listStore, list, router, undefined, undefined, {});
}

beforeEach(() => {
    overlayMock.mockClear();
});

test('Return a disabled item action config without callback if the row has no id', () => {
    const itemAction = createItemAction();

    expect(itemAction.getItemActionConfig({id: 'request-1'})).toEqual({
        icon: 'su-information',
        disabled: false,
        onClick: expect.anything(),
    });

    expect(itemAction.getItemActionConfig(undefined)).toEqual({
        icon: 'su-information',
        disabled: true,
        onClick: undefined,
    });
});

test('Render nothing before a request was loaded', () => {
    expect(createItemAction().getNode()).toEqual(null);
});

test('Load the clicked request and show it in a read-only overlay', () => {
    const getPromise = Promise.resolve(request);
    ResourceRequester.get.mockReturnValue(getPromise);

    const itemAction = createItemAction();
    const onClick = itemAction.getItemActionConfig({id: 'request-1'}).onClick;
    if (!onClick) {
        throw new Error('The onClick callback should not be undefined in this case');
    }
    onClick('request-1', 0);

    expect(ResourceRequester.get).toHaveBeenCalledWith('workflow_transition_requests', {id: 'request-1'});

    return getPromise.then(() => {
        render(itemAction.getNode());

        expect(overlayMock.mock.calls[0][0]).toEqual(expect.objectContaining({
            canAct: false,
            mode: 'view',
            open: true,
            request,
        }));
    });
});

test('Close the overlay again', () => {
    const getPromise = Promise.resolve(request);
    ResourceRequester.get.mockReturnValue(getPromise);

    const itemAction = createItemAction();
    const onClick = itemAction.getItemActionConfig({id: 'request-1'}).onClick;
    if (!onClick) {
        throw new Error('The onClick callback should not be undefined in this case');
    }
    onClick('request-1', 0);

    return getPromise.then(() => {
        render(itemAction.getNode());

        overlayMock.mock.calls[0][0].onClose();

        expect(itemAction.getNode()).toEqual(null);
    });
});

test('Show the request clicked last, even if an earlier click resolves after it', () => {
    let resolveFirst = () => {};
    const firstPromise = new Promise((resolve) => {
        resolveFirst = () => resolve({...request, id: 'request-1'});
    });
    const secondPromise = Promise.resolve({...request, id: 'request-2'});
    ResourceRequester.get.mockReturnValueOnce(firstPromise).mockReturnValueOnce(secondPromise);

    const itemAction = createItemAction();
    itemAction.handleClick('request-1');
    itemAction.handleClick('request-2');

    return secondPromise.then(() => {
        resolveFirst();

        return firstPromise;
    }).then(() => {
        expect(itemAction.request?.id).toEqual('request-2');
    });
});

test('Do not reopen the overlay when a request resolves after it was closed', () => {
    let resolveRequest = () => {};
    const pendingPromise = new Promise((resolve) => {
        resolveRequest = () => resolve(request);
    });
    ResourceRequester.get.mockReturnValue(pendingPromise);

    const itemAction = createItemAction();
    itemAction.handleClick('request-1');
    itemAction.handleClose();
    resolveRequest();

    return pendingPromise.then(() => {
        expect(itemAction.getNode()).toEqual(null);
    });
});
