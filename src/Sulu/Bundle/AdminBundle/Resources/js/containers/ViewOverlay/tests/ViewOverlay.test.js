// @flow
import React from 'react';
import {observable} from 'mobx';
import {act, render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {createMemoryHistory} from 'history';
import Router, {routeRegistry} from '../../../services/Router';
import toolbarStorePool, {DEFAULT_STORE_KEY} from '../../Toolbar/stores/toolbarStorePool';
import {withToolbar} from '../../Toolbar';
import {viewRegistry} from '../../ViewRenderer';
import {mockResizeObserver} from '../../../utils/TestHelper';
import ViewOverlay from '../ViewOverlay';

mockResizeObserver();

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

const showSuccess = observable.box(false);
const errors = observable.array([]);
let mockUpdateRouteHook = jest.fn().mockReturnValue(true);

let mockRouter: Router;
let parentRouter: Router;

function TestViewOverlay(props: Object) {
    return <ViewOverlay parentRouter={parentRouter} {...props} />;
}

class TestView extends React.Component<*> {
    constructor(props: *) {
        super(props);

        mockRouter = props.router;
    }

    componentDidMount() {
        this.props.router.addUpdateRouteHook(mockUpdateRouteHook, 2048);
    }

    render() {
        const {router} = this.props;

        return <h1>{router.route.name + ' ' + router.attributes.id}</h1>;
    }
}

const TestViewWithToolbar = withToolbar(TestView, () => ({
    backButton: {onClick: jest.fn()},
    errors,
    items: [{icon: 'su-save', label: 'Save', onClick: jest.fn(), type: 'button'}],
    showSuccess,
}));

function getCloseButton() {
    const closeButton = document.querySelector('.closeButton button');
    if (!closeButton) {
        throw new Error('The close button of the overlay is missing.');
    }

    return closeButton;
}

beforeEach(() => {
    routeRegistry.clear();
    viewRegistry.clear();
    routeRegistry.addCollection([
        {name: 'test_form', options: {backView: 'test_list'}, path: '/test/:id', type: 'test_view'},
        {name: 'test_localized', path: '/localized/:locale/:id', type: 'test_view'},
        {name: 'test_list', path: '/tests', type: 'test_view'},
        {name: 'test_tabs', path: '/tabs/:id', rerenderAttributes: ['webspace'], type: 'test_view'},
        {name: 'test_tabs.list', parent: 'test_tabs', path: '/tabs/:id/list', type: 'test_view'},
    ]);
    viewRegistry.add('test_view', TestViewWithToolbar);
    showSuccess.set(false);
    errors.clear();
    mockUpdateRouteHook = jest.fn().mockReturnValue(true);
    window.location.hash = '#/page';
    parentRouter = new Router(createMemoryHistory());
    parentRouter.navigate('test_list');
});

// lets a navigation through only when it is repeated with the same update method, as the dirty warning of the form does
function mockDirtyViewHook() {
    let postponedUpdateRouteMethod;

    mockUpdateRouteHook = jest.fn((route, attributes, updateRouteMethod) => {
        if (postponedUpdateRouteMethod === updateRouteMethod) {
            return true;
        }

        postponedUpdateRouteMethod = updateRouteMethod;
        return false;
    });
}

test('Render the view of the route with the own router and leave the browser url alone', () => {
    render(<TestViewOverlay attributes={{id: 5}} onClose={jest.fn()} route="test_form" title="Edit" />);

    expect(screen.getByRole('heading', {name: 'test_form 5'})).toBeInTheDocument();
    expect(window.location.hash).toEqual('#/page');
});

test('Render the toolbar of the view in the overlay instead of the page toolbar', () => {
    const pageToolbarStore = toolbarStorePool.hasStore(DEFAULT_STORE_KEY)
        ? toolbarStorePool.getStore(DEFAULT_STORE_KEY)
        : toolbarStorePool.createStore(DEFAULT_STORE_KEY);

    render(<TestViewOverlay attributes={{id: 5}} onClose={jest.fn()} route="test_form" title="Edit" />);

    expect(screen.getByRole('dialog', {name: 'Edit'})).toContainElement(screen.getByRole('button', {name: /Save/}));
    expect(screen.queryByLabelText('su-angle-left')).not.toBeInTheDocument();
    expect(pageToolbarStore.getItemsConfig()).toEqual([]);
});

test('Turn the close button into a check while the view shows its success', () => {
    render(<TestViewOverlay attributes={{id: 5}} onClose={jest.fn()} route="test_form" title="Edit" />);

    expect(screen.queryByRole('status')).toBeEmptyDOMElement();

    act(() => {
        showSuccess.set(true);
    });

    expect(screen.getByRole('status')).toHaveTextContent('sulu_admin.success');
});

test('Show the errors of the view below the toolbar inside the overlay', () => {
    render(<TestViewOverlay attributes={{id: 5}} onClose={jest.fn()} route="test_form" title="Edit" />);

    act(() => {
        errors.push('Save failed');
    });

    const message = screen.getByText(/Save failed/);
    expect(screen.getByRole('dialog', {name: 'Edit'})).toContainElement(message);
    expect(screen.getByLabelText('su-save').compareDocumentPosition(message))
        .toBe(Node.DOCUMENT_POSITION_FOLLOWING);
});

test('Keep the locale of the overlay if a view navigates without one', () => {
    render(
        <TestViewOverlay attributes={{id: 5, locale: 'de'}} onClose={jest.fn()} route="test_localized" title="Edit" />
    );

    act(() => {
        mockRouter.navigate('test_localized', {id: 6});
    });

    expect(screen.getByRole('heading', {name: 'test_localized 6'})).toBeInTheDocument();
    expect(mockRouter.attributes.locale).toEqual('de');
});

test('Close the overlay instead of rendering the back view of the view', () => {
    const onClose = jest.fn();
    render(<TestViewOverlay attributes={{id: 5}} onClose={onClose} route="test_form" title="Edit" />);

    act(() => {
        mockRouter.restore('test_list', {});
    });

    expect(onClose).toHaveBeenCalledTimes(1);
    expect(screen.getByRole('heading', {name: 'test_form 5'})).toBeInTheDocument();
});

test('Close the overlay if no view vetoes it', async() => {
    const onClose = jest.fn();
    render(<TestViewOverlay attributes={{id: 5}} onClose={onClose} route="test_form" title="Edit" />);

    await userEvent.click(getCloseButton());

    expect(onClose).toHaveBeenCalledTimes(1);
});

test('Keep the overlay open if a view vetoes the close, until it closes it itself', async() => {
    mockUpdateRouteHook.mockReturnValue(false);
    const onClose = jest.fn();
    render(<TestViewOverlay attributes={{id: 5}} onClose={onClose} route="test_form" title="Edit" />);

    await userEvent.click(getCloseButton());

    expect(onClose).not.toHaveBeenCalled();
    const [closeRoute, , closeOverlay] = mockUpdateRouteHook.mock.calls[0];
    expect(closeRoute.name).toEqual('sulu_admin.view_overlay_close');

    closeOverlay(closeRoute.name, {});
    expect(onClose).toHaveBeenCalledTimes(1);
});

test('Destroy the own router when unmounted', () => {
    const destroySpy = jest.spyOn(Router.prototype, 'destroy');
    const {unmount} = render(
        <TestViewOverlay attributes={{id: 5}} onClose={jest.fn()} route="test_form" title="Edit" />
    );

    unmount();

    expect(destroySpy).toHaveBeenCalledTimes(1);
});

test('Reuse the toolbar store of a depth and give a nested overlay its own', () => {
    const storeKeys = () => Object.keys(toolbarStorePool.stores).filter((key) => key.includes('view_overlay'));

    const {unmount: unmountFirst} = render(
        <TestViewOverlay attributes={{id: 5}} onClose={jest.fn()} route="test_form" title="First" />
    );
    unmountFirst();
    const storeKeysAfterFirst = storeKeys();
    expect(storeKeysAfterFirst).toContain('sulu_admin.view_overlay_1');

    const {unmount: unmountSecond} = render(
        <TestViewOverlay attributes={{id: 6}} onClose={jest.fn()} route="test_form" title="Second" />
    );
    expect(storeKeys()).toEqual(storeKeysAfterFirst);

    render(<TestViewOverlay attributes={{id: 7}} onClose={jest.fn()} route="test_form" title="Nested" />);
    expect(storeKeys()).toContain('sulu_admin.view_overlay_2');

    unmountSecond();
});

test('Ask the view before the parent router leaves the page of the overlay', () => {
    mockDirtyViewHook();
    render(<TestViewOverlay attributes={{id: 5}} onClose={jest.fn()} route="test_form" title="Edit" />);

    act(() => {
        parentRouter.navigate('test_form', {id: 1});
    });
    expect(parentRouter.route.name).toEqual('test_list');

    // leaving once more while the warning is still open must not count as its confirmation
    act(() => {
        parentRouter.navigate('test_form', {id: 1});
    });
    expect(parentRouter.route.name).toEqual('test_list');

    const [closeRoute, , leave] = mockUpdateRouteHook.mock.calls[mockUpdateRouteHook.mock.calls.length - 1];
    expect(closeRoute.name).toEqual('sulu_admin.view_overlay_close');

    act(() => {
        leave(closeRoute.name, {});
    });
    expect(parentRouter.route.name).toEqual('test_form');
    expect(parentRouter.attributes).toEqual({id: 1});
});

test('Do not ask the view if the parent router keeps the view of the overlay', () => {
    mockUpdateRouteHook.mockReturnValue(false);
    render(<TestViewOverlay attributes={{id: 5}} onClose={jest.fn()} route="test_form" title="Edit" />);

    act(() => {
        parentRouter.navigate('test_list', {page: 2});
    });

    expect(parentRouter.attributes).toEqual({page: 2});
    expect(mockUpdateRouteHook).not.toHaveBeenCalled();
});

test('Stop watching the parent router when unmounted', () => {
    mockUpdateRouteHook.mockReturnValue(false);
    const {unmount} = render(
        <TestViewOverlay attributes={{id: 5}} onClose={jest.fn()} route="test_form" title="Edit" />
    );

    unmount();
    parentRouter.navigate('test_form', {id: 1});

    expect(parentRouter.route.name).toEqual('test_form');
    expect(mockUpdateRouteHook).not.toHaveBeenCalled();
});

test.each([
    ['another id, as the tabs around the page reload for it', {id: 2, webspace: 'a'}],
    ['a rerender attribute of a parent view of the page', {id: 1, webspace: 'b'}],
])('Ask the view before the parent router changes %s', (description, attributes) => {
    mockUpdateRouteHook.mockReturnValue(false);
    parentRouter.navigate('test_tabs.list', {id: 1, webspace: 'a'});
    render(<TestViewOverlay attributes={{id: 5}} onClose={jest.fn()} route="test_form" title="Edit" />);

    act(() => {
        parentRouter.navigate('test_tabs.list', attributes);
    });

    expect(mockUpdateRouteHook).toHaveBeenCalledTimes(1);
    expect(parentRouter.attributes).toEqual({id: 1, webspace: 'a'});
});

test('Ask the view again for the next navigation of the parent router after one was confirmed', () => {
    mockUpdateRouteHook.mockReturnValue(false);
    render(<TestViewOverlay attributes={{id: 5}} onClose={jest.fn()} route="test_form" title="Edit" />);

    act(() => {
        parentRouter.navigate('test_form', {id: 1});
    });
    const [closeRoute, , leave] = mockUpdateRouteHook.mock.calls[0];
    act(() => {
        leave(closeRoute.name, {});
    });
    act(() => {
        parentRouter.navigate('test_list');
    });

    expect(mockUpdateRouteHook).toHaveBeenCalledTimes(2);
    expect(parentRouter.route.name).toEqual('test_form');
});

test('Ask the view before hooks of the parent router which react to the navigation', () => {
    mockUpdateRouteHook.mockReturnValue(false);
    const reactingHook = jest.fn().mockReturnValue(true);
    parentRouter.addUpdateRouteHook(reactingHook, 1024);
    render(<TestViewOverlay attributes={{id: 5}} onClose={jest.fn()} route="test_form" title="Edit" />);

    act(() => {
        parentRouter.navigate('test_form', {id: 1});
    });

    expect(reactingHook).not.toHaveBeenCalled();
});

test('Leave the closing of the window to the own router', () => {
    render(<TestViewOverlay attributes={{id: 5}} onClose={jest.fn()} route="test_form" title="Edit" />);

    expect(parentRouter.sortedUpdateRouteHooks.every((hook) => hook())).toEqual(true);
    expect(mockUpdateRouteHook).not.toHaveBeenCalled();
});
