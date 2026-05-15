/* eslint-disable flowtype/require-valid-file-annotation */
import {render} from '@testing-library/react';
import mockReact from 'react';
import ResourceStore from '../../../stores/ResourceStore';

const React = mockReact;
const mockSidebarConfigGetters = [];

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
    };

    render() {
        return <div>form view mock</div>;
    }
});

jest.mock('../../../containers/Sidebar/withSidebar', () => jest.fn((Component, sidebar) => {
    return class WithSidebarMock extends Component {
        render() {
            mockSidebarConfigGetters.push(() => sidebar.call(this));

            return super.render();
        }
    };
}));

const getLatestSidebarConfig = () => mockSidebarConfigGetters[mockSidebarConfigGetters.length - 1]();

beforeEach(() => {
    jest.resetModules();
    mockSidebarConfigGetters.splice(0, mockSidebarConfigGetters.length);
});

test('Should render Form view', () => {
    const resourceStore = new ResourceStore('snippet', 1);

    const route = {
        options: {
            previewCondition: 'nodeType == 1',
        },
    };
    const router = {
        route,
    };

    const PreviewForm = require('../PreviewForm').default;

    const {asFragment} = render(
        <PreviewForm locales={[]} resourceStore={resourceStore} route={route} router={router} />
    );
    expect(asFragment()).toMatchSnapshot();
});

test('Should initialize preview sidebar per default when previewCondition is not set', () => {
    const resourceStore = new ResourceStore('snippet', 1);

    const route = {
        options: {},
    };
    const router = {
        route,
    };

    const PreviewForm = require('../PreviewForm').default;
    render(<PreviewForm locales={[]} resourceStore={resourceStore} route={route} router={router} />);

    const sidebarConfig = getLatestSidebarConfig();

    expect(sidebarConfig.view).toEqual('sulu_preview.preview');
    expect(sidebarConfig.sizes).toEqual(['medium', 'large']);
    expect(sidebarConfig.props.router).toEqual(router);
    expect(sidebarConfig.props.formStore).toBeDefined();

    const jexl = require('jexl');
    expect(jexl.evalSync).not.toBeCalled();
});

test('Should initialize preview sidebar when previewCondition evaluates to true', () => {
    const resourceStore = new ResourceStore('snippet', 1);

    const route = {
        options: {
            previewCondition: 'nodeType == 1',
        },
    };
    const router = {
        route,
    };

    const PreviewForm = require('../PreviewForm').default;
    render(<PreviewForm locales={[]} resourceStore={resourceStore} route={route} router={router} />);

    const sidebarConfig = getLatestSidebarConfig();

    expect(sidebarConfig.view).toEqual('sulu_preview.preview');
    expect(sidebarConfig.sizes).toEqual(['medium', 'large']);
    expect(sidebarConfig.props.router).toEqual(router);
    expect(sidebarConfig.props.formStore).toBeDefined();

    const jexl = require('jexl');
    expect(jexl.evalSync).toBeCalledWith( 'nodeType == 1', {testKey: 'test-value'});
});

test('Should not initialize preview sidebar when previewCondition evaluates to true', () => {
    const resourceStore = new ResourceStore('snippet', 1);

    const route = {
        options: {
            previewCondition: 'nodeType == 2',
        },
    };
    const router = {
        route,
    };

    const PreviewForm = require('../PreviewForm').default;
    render(<PreviewForm locales={[]} resourceStore={resourceStore} route={route} router={router} />);

    const sidebarConfig = getLatestSidebarConfig();

    expect(sidebarConfig).toEqual(null);

    const jexl = require('jexl');
    expect(jexl.evalSync).toBeCalledWith( 'nodeType == 2', {testKey: 'test-value'});
});
