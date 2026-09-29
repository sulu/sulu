/* eslint-disable flowtype/require-valid-file-annotation */
import mockReact from 'react';
import {render, screen} from '@testing-library/react';
import ResourceStore from '../../../stores/ResourceStore';

const React = mockReact;

jest.mock('../../../stores/ResourceStore', () => jest.fn());

jest.mock('jexl', () => ({
    evalSync: jest.fn().mockImplementation((expression) => {
        if (undefined === expression) {
            throw new Error('Expression cannot be undefined');
        }

        return expression === 'nodeType == 1';
    }),
}));

jest.mock('../../Form', () => class FormMock extends mockReact.Component<*> {
    resourceFormStore = {
        data: {
            testKey: 'test-value',
        },
        resourceKey: 'snippets',
    };

    render() {
        return <div>form view mock</div>;
    }
});

jest.mock('../../../containers/Sidebar/stores/sidebarStore', () => ({
    __esModule: true,
    default: {
        clearConfig: jest.fn(),
        setConfig: jest.fn(),
    },
}));

beforeEach(() => {
    jest.clearAllMocks();
});

function renderPreviewForm(previewCondition) {
    const resourceStore = new ResourceStore('snippet', 1);
    const route = {
        options: previewCondition ? {previewCondition} : {},
    };
    const router = {
        addUpdateRouteHook: jest.fn().mockReturnValue(jest.fn()),
        attributes: {},
        route,
    };
    const PreviewForm = require('../PreviewForm').default;

    return {
        router,
        ...render(<PreviewForm locales={[]} resourceStore={resourceStore} route={route} router={router} />),
    };
}

function getSidebarStore() {
    return require('../../../containers/Sidebar/stores/sidebarStore').default;
}

test('Should render Form view', () => {
    renderPreviewForm('nodeType == 1');

    expect(screen.getByText('form view mock')).toBeInTheDocument();
});

test('Should initialize preview sidebar per default when previewCondition is not set', () => {
    const {router} = renderPreviewForm();
    const sidebarStore = getSidebarStore();

    expect(sidebarStore.setConfig).toHaveBeenCalledWith({
        view: 'sulu_preview.preview',
        sizes: ['medium', 'large'],
        props: {
            router,
            formStore: expect.objectContaining({data: {testKey: 'test-value'}}),
            key: 'snippets',
        },
    });

    const jexl = require('jexl');
    expect(jexl.evalSync).not.toHaveBeenCalled();
});

test('Should initialize preview sidebar when previewCondition evaluates to true', () => {
    const {router} = renderPreviewForm('nodeType == 1');
    const sidebarStore = getSidebarStore();

    expect(sidebarStore.setConfig).toHaveBeenCalledWith({
        view: 'sulu_preview.preview',
        sizes: ['medium', 'large'],
        props: {
            router,
            formStore: expect.objectContaining({data: {testKey: 'test-value'}}),
            key: 'snippets',
        },
    });

    const jexl = require('jexl');
    expect(jexl.evalSync).toHaveBeenCalledWith('nodeType == 1', {
        __routeAttributes: {},
        testKey: 'test-value',
    });
});

test('Should not initialize preview sidebar when previewCondition evaluates to false', () => {
    renderPreviewForm('nodeType == 2');
    const sidebarStore = getSidebarStore();

    expect(sidebarStore.setConfig).not.toHaveBeenCalled();
    expect(sidebarStore.clearConfig).toHaveBeenCalledWith();

    const jexl = require('jexl');
    expect(jexl.evalSync).toHaveBeenCalledWith('nodeType == 2', {
        __routeAttributes: {},
        testKey: 'test-value',
    });
});
