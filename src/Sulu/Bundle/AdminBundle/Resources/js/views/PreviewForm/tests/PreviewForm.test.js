// @flow
import {mount, render} from 'enzyme';
import mockReact from 'react';
import {findWithHighOrderFunction} from '../../../utils/TestHelper';
import ResourceStore from '../../../stores/ResourceStore';
import type {Route} from '../../../services';

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
    };

    render() {
        return <div>form view mock</div>;
    }
});

jest.mock('../../../containers/Sidebar/withSidebar', () => jest.fn((Component) => Component));

beforeEach(() => {
    jest.resetModules();
});

test('Should render Form view', () => {
    const resourceStore = new ResourceStore('snippet', 1);

    const route: Route = ({
        options: {
            previewCondition: 'nodeType == 1',
        },
    }: any);
    const router = ({
        route: route,
    }: any);

    const PreviewForm = (require('../PreviewForm').default: any);

    expect(render(
        <PreviewForm locales={[]} resourceStore={resourceStore} route={route} router={router} />
    )).toMatchSnapshot();
});

test('Should initialize preview sidebar when previewCondition evaluates to true', () => {
    const resourceStore = new ResourceStore('snippet', 1);

    const route: Route = ({
        options: {
            previewCondition: 'nodeType == 1',
        },
    }: any);
    const router = ({
        route: route,
    }: any);

    // require preview form to trigger call of withSidebar mock and retrieve passed function
    const PreviewForm = (require('../PreviewForm').default: any);
    const withSidebar = (require('../../../containers/Sidebar/withSidebar'): any);
    const Form = (require('../../Form'): any);
    const sidebarFunction = findWithHighOrderFunction(withSidebar, Form);

    // mount PreviewForm and call function that was passed to withSidebar
    const previewForm = mount(<PreviewForm locales={[]} resourceStore={resourceStore} route={route} router={router} />);
    const sidebarConfig = sidebarFunction.call(previewForm.instance());

    // check if function that was passed to withSidebar returns the correct SidebarConfig
    expect(sidebarConfig.view).toEqual('sulu_preview.preview');
    expect(sidebarConfig.sizes).toEqual(['medium', 'large']);
    expect(sidebarConfig.props.router).toEqual(router);
    expect(sidebarConfig.props.formStore).toBeDefined();

    // check if evalSync was called with correct parameters during function call
    const jexl = (require('jexl'): any);
    expect(jexl.evalSync).toBeCalledWith( 'nodeType == 1', {testKey: 'test-value'});
});
