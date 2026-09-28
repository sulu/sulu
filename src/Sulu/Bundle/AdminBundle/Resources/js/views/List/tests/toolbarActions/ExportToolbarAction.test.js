// @flow
import {observable as mockObservable, observable} from 'mobx';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import ListStore from '../../../../containers/List/stores/ListStore';
import Router from '../../../../services/Router';
import ResourceStore from '../../../../stores/ResourceStore';
import List from '../../../../views/List';
import ExportToolbarAction from '../../toolbarActions/ExportToolbarAction';
import resourceRouteRegistry from '../../../../services/ResourceRequester/registries/resourceRouteRegistry';

jest.mock('../../../../utils/Translator');

jest.mock('../../../../containers/List/stores/ListStore', () => jest.fn(function(resourceKey) {
    this.data = [];
    this.filterQueryOption = {};
    this.searchTerm = mockObservable.box();
    this.resourceKey = resourceKey;
}));

jest.mock('../../../../views/List/List', () => jest.fn(function() {
    this.locale = mockObservable.box();
}));

jest.mock('../../../../services/Router/Router', () => jest.fn());

jest.mock('../../../../services/ResourceRequester/registries/resourceRouteRegistry', () => ({
    getUrl: jest.fn(),
}));

jest.mock('../../../../components/Overlay', () => {
    const React = require('react');

    return jest.fn((props) => {
        if (!props.open) {
            return null;
        }

        return (
            <div data-testid="export-overlay" data-title={props.title}>
                <button onClick={props.onConfirm} type="button">
                    {props.confirmText}
                </button>
            </div>
        );
    });
});

function createExportToolbarAction(options = {}) {
    const router = new Router({});
    const listStore = new ListStore('test', 'test', 'test', {page: observable.box(1)});
    const list = new List({
        route: router.route,
        router,
    });
    const locales = [];
    const resourceStore = new ResourceStore('test');

    return new ExportToolbarAction(listStore, list, router, locales, resourceStore, options);
}

test('Return config for toolbar item', () => {
    const exportToolbarAction = createExportToolbarAction();

    expect(exportToolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        disabled: true,
        icon: 'su-download',
        label: 'sulu_admin.export',
        type: 'button',
    }));
});

test('Return config for non-empty toolbar item', () => {
    const exportToolbarAction = createExportToolbarAction();

    exportToolbarAction.listStore.data.push({});

    expect(exportToolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        disabled: false,
        icon: 'su-download',
        label: 'sulu_admin.export',
        type: 'button',
    }));
});

test('Export current result when button is clicked and dialog is confirmed', async() => {
    const user = userEvent.setup();
    window.location.assign = jest.fn();

    const exportToolbarAction = createExportToolbarAction();
    exportToolbarAction.listStore.data.push({});

    resourceRouteRegistry.getUrl.mockReturnValue('/list');

    const toolbarItemConfig = exportToolbarAction.getToolbarItemConfig();
    toolbarItemConfig.onClick();

    render(exportToolbarAction.getNode());
    await user.click(screen.getByRole('button', {name: 'sulu_admin.export'}));

    expect(resourceRouteRegistry.getUrl).toHaveBeenCalledWith(
        'list',
        'test',
        {
            _format: 'csv',
            delimiter: ';',
            enclosure: '"',
            escape: '\\',
            filter: undefined,
            flat: true,
            locale: undefined,
            newLine: '\\n',
            search: undefined,
        }
    );

    expect(window.location.assign).toHaveBeenCalledWith('/list');
});

test('Export current result with applied filter and search when button is clicked and dialog is confirmed', async() => {
    const user = userEvent.setup();
    window.location.assign = jest.fn();

    const exportToolbarAction = createExportToolbarAction();
    exportToolbarAction.listStore.data.push({});
    exportToolbarAction.listStore.searchTerm.set('search');
    // $FlowFixMe
    exportToolbarAction.listStore.filterQueryOption = {test: {eq: 'Test'}};

    resourceRouteRegistry.getUrl.mockReturnValue('/list');

    const toolbarItemConfig = exportToolbarAction.getToolbarItemConfig();
    toolbarItemConfig.onClick();

    render(exportToolbarAction.getNode());
    await user.click(screen.getByRole('button', {name: 'sulu_admin.export'}));

    expect(resourceRouteRegistry.getUrl).toHaveBeenCalledWith(
        'list',
        'test',
        {
            _format: 'csv',
            delimiter: ';',
            enclosure: '"',
            escape: '\\',
            filter: {test: {eq: 'Test'}},
            flat: true,
            locale: undefined,
            newLine: '\\n',
            search: 'search',
        }
    );

    expect(window.location.assign).toHaveBeenCalledWith('/list');
});
