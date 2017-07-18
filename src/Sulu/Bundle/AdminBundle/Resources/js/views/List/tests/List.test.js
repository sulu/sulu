/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {mount, render} from 'enzyme';
import List from '../List';

jest.mock('../../../containers/Datagrid/stores/DatagridStore', () => function() {
    this.isLoading = false;
    this.data = [
        {
            id: 1,
            title: 'Title 1',
            description: 'Description 1',
        },
        {
            id: 2,
            title: 'Title 2',
            description: 'Description 2',
        },
    ];
    this.getPage = function() {
        return 2;
    };
    this.pageCount = 3;
    this.getFields = function() {
        return {
            title: {},
            description: {},
        };
    };
    this.destroy = jest.fn();
});

jest.mock('../../../stores/ResourceMetadataStore', () => ({
    getBaseUrl: jest.fn(),
}));

jest.mock('../../../containers/Toolbar/withToolbar', () => function(Component) {
    return Component;
});

test('Should render the datagrid with the correct resourceKey', () => {
    const router = {
        bindQuery: jest.fn(),
        route: {
            options: {
                resourceKey: 'snippets',
            },
        },
    };

    const list = render(<List router={router} />);
    expect(list).toMatchSnapshot();
});

test('Should bind the query parameter to the router', () => {
    const router = {
        bindQuery: jest.fn(),
        unbindQuery: jest.fn(),
        route: {
            options: {
                resourceKey: 'snippets',
            },
        },
    };

    const list = mount(<List router={router} />);
    expect(router.bindQuery).toBeCalledWith('page', undefined, '1');

    list.unmount();
    expect(router.unbindQuery).toBeCalledWith('page');
});

test('Should navigate when click on pencil button', () => {
    const router = {
        navigate: jest.fn(),
        bindQuery: jest.fn(),
        route: {
            options: {
                editLink: 'editLink',
            },
        },
    };

    const list = mount(<List router={router} />);
    list.find('ButtonCell button').at(0).simulate('click');
    expect(router.navigate).toBeCalledWith('editLink', {uuid: 1});
});
