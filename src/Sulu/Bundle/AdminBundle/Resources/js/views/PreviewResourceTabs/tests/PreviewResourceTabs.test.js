/* eslint-disable flowtype/require-valid-file-annotation */
import {mount, render} from 'enzyme';
import mockReact from 'react';
import {findWithHighOrderFunction} from '../../../utils/TestHelper';
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

jest.mock('../../ResourceTabs', () => class ResourceTabsMock extends mockReact.Component<*> {
    render() {
        return <div>resource tabs</div>;
    }
});

jest.mock('../../../containers/Sidebar/withSidebar', () => jest.fn((Component) => Component));

beforeEach(() => {
    jest.resetModules();
});

test('Should render ResourceTabs view', () => {
    const resourceStore = new ResourceStore('snippet', 1);

    const route = {
        options: {
            previewCondition: 'nodeType == 1',
        },
    };
    const router = {
        route,
    };

    const PreviewResourceTabs = require('../PreviewResourceTabs').default;

    expect(render(
        <PreviewResourceTabs locales={[]} resourceStore={resourceStore} route={route} router={router} />
    )).toMatchSnapshot();
});

test('Should initialize preview sidebar per default when previewCondition is not set', () => {
    const resourceStore = new ResourceStore('snippet', 1);

    const route = {
        options: {},
    };
    const router = {
        route,
    };

    // require preview resource-tabs to trigger call of withSidebar mock and retrieve passed function
    const PreviewResourceTabs = require('../PreviewResourceTabs').default;
    const withSidebar = require('../../../containers/Sidebar/withSidebar');
    const ResourceTabs = require('../../ResourceTabs');
    const sidebarFunction = findWithHighOrderFunction(withSidebar, ResourceTabs);

    // mount PreviewResourceTabs and call function that was passed to withSidebar
    const previewResourceTabs = mount(<PreviewResourceTabs
        locales={[]}
        resourceStore={resourceStore}
        route={route}
        router={router}
    />);
    const sidebarConfig = sidebarFunction.call(previewResourceTabs.instance());

    // check if function that was passed to withSidebar returns the correct SidebarConfig
    expect(sidebarConfig.view).toEqual('sulu_preview.preview');
    expect(sidebarConfig.sizes).toEqual(['medium', 'large']);
    expect(sidebarConfig.props.router).toEqual(router);

    // check if evalSync was called with correct parameters during function call
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

    // require preview resource-tabs to trigger call of withSidebar mock and retrieve passed function
    const PreviewResourceTabs = require('../PreviewResourceTabs').default;
    const withSidebar = require('../../../containers/Sidebar/withSidebar');
    const ResourceTabs = require('../../ResourceTabs');
    const sidebarFunction = findWithHighOrderFunction(withSidebar, ResourceTabs);

    // mount PreviewResourceTabs and call function that was passed to withSidebar
    const previewResourceTabs = mount(<PreviewResourceTabs
        locales={[]}
        resourceStore={resourceStore}
        route={route}
        router={router}
    />);
    const sidebarConfig = sidebarFunction.call(previewResourceTabs.instance());

    // check if function that was passed to withSidebar returns the correct SidebarConfig
    expect(sidebarConfig.view).toEqual('sulu_preview.preview');
    expect(sidebarConfig.sizes).toEqual(['medium', 'large']);
    expect(sidebarConfig.props.router).toEqual(router);

    // check if evalSync was called with correct parameters during function call
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

    // require preview resource-tabs to trigger call of withSidebar mock and retrieve passed function
    const PreviewResourceTabs = require('../PreviewResourceTabs').default;
    const withSidebar = require('../../../containers/Sidebar/withSidebar');
    const ResourceTabs = require('../../ResourceTabs');
    const sidebarFunction = findWithHighOrderFunction(withSidebar, ResourceTabs);

    // mount PreviewResourceTabs and call function that was passed to withSidebar
    const previewResourceTabs = mount(<PreviewResourceTabs
        locales={[]}
        resourceStore={resourceStore}
        route={route}
        router={router}
    />);
    const sidebarConfig = sidebarFunction.call(previewResourceTabs.instance());

    // check if function that was passed to withSidebar returns the correct SidebarConfig
    expect(sidebarConfig).toEqual(null);

    // check if evalSync was called with correct parameters during function call
    const jexl = require('jexl');
    expect(jexl.evalSync).toBeCalledWith( 'nodeType == 2', {testKey: 'test-value'});
});
