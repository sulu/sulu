// @flow
import React from 'react';
import {observable} from 'mobx';
import {act, render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
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
let mockUpdateRouteHook = jest.fn().mockReturnValue(true);

class TestView extends React.Component<*> {
    componentDidMount() {
        this.props.router.addUpdateRouteHook(mockUpdateRouteHook, 2048);
    }

    render() {
        const {router} = this.props;

        return <h1>{router.route.name + ' ' + router.attributes.id}</h1>;
    }
}

const TestViewWithToolbar = withToolbar(TestView, () => ({
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
        {name: 'test_form', path: '/test/:id', type: 'test_view'},
    ]);
    viewRegistry.add('test_view', TestViewWithToolbar);
    showSuccess.set(false);
    mockUpdateRouteHook = jest.fn().mockReturnValue(true);
    window.location.hash = '#/page';
});

test('Render the view of the route with the own router and leave the browser url alone', () => {
    render(<ViewOverlay attributes={{id: 5}} onClose={jest.fn()} route="test_form" title="Edit" />);

    expect(screen.getByRole('heading', {name: 'test_form 5'})).toBeInTheDocument();
    expect(window.location.hash).toEqual('#/page');
});

test('Render the toolbar of the view in the overlay instead of the page toolbar', () => {
    const pageToolbarStore = toolbarStorePool.hasStore(DEFAULT_STORE_KEY)
        ? toolbarStorePool.getStore(DEFAULT_STORE_KEY)
        : toolbarStorePool.createStore(DEFAULT_STORE_KEY);

    render(<ViewOverlay attributes={{id: 5}} onClose={jest.fn()} route="test_form" title="Edit" />);

    expect(screen.getByRole('dialog', {name: 'Edit'})).toContainElement(screen.getByRole('button', {name: /Save/}));
    expect(pageToolbarStore.getItemsConfig()).toEqual([]);
});

test('Turn the close button into a check while the view shows its success', () => {
    render(<ViewOverlay attributes={{id: 5}} onClose={jest.fn()} route="test_form" title="Edit" />);

    expect(screen.queryByRole('status')).toBeEmptyDOMElement();

    act(() => {
        showSuccess.set(true);
    });

    expect(screen.getByRole('status')).toHaveTextContent('sulu_admin.success');
});

test('Close the overlay if no view vetoes it', async() => {
    const onClose = jest.fn();
    render(<ViewOverlay attributes={{id: 5}} onClose={onClose} route="test_form" title="Edit" />);

    await userEvent.click(getCloseButton());

    expect(onClose).toHaveBeenCalledTimes(1);
});

test('Keep the overlay open if a view vetoes the close, until it closes it itself', async() => {
    mockUpdateRouteHook.mockReturnValue(false);
    const onClose = jest.fn();
    render(<ViewOverlay attributes={{id: 5}} onClose={onClose} route="test_form" title="Edit" />);

    await userEvent.click(getCloseButton());

    expect(onClose).not.toHaveBeenCalled();
    const [closeRoute, , closeOverlay] = mockUpdateRouteHook.mock.calls[0];
    expect(closeRoute.name).toEqual('sulu_admin.view_overlay_close');

    closeOverlay(closeRoute.name, {});
    expect(onClose).toHaveBeenCalledTimes(1);
});

test('Destroy the own router and toolbar store when unmounted', () => {
    const destroySpy = jest.spyOn(Router.prototype, 'destroy');
    const {unmount} = render(<ViewOverlay attributes={{id: 5}} onClose={jest.fn()} route="test_form" title="Edit" />);
    const storeKeys = Object.keys(toolbarStorePool.stores).filter((key) => toolbarStorePool.hasStore(key));

    unmount();

    expect(destroySpy).toHaveBeenCalledTimes(1);
    expect(Object.keys(toolbarStorePool.stores).filter((key) => toolbarStorePool.hasStore(key)))
        .toHaveLength(storeKeys.length - 1);
});
