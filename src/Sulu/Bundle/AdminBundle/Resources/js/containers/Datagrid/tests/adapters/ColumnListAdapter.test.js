// @flow
import React from 'react';
import {mount, render} from 'enzyme';
import ColumnListAdapter from '../../adapters/ColumnListAdapter';

test('Render data', () => {
    const data = [
        {
            id: 1,
            children: [
                {
                    children: [],
                    data: {
                        id: 3,
                        title: 'Page 1.1',
                        hasSub: false,
                    },
                },
            ],
            data: {
                id: 1,
                title: 'Page 1',
                hasSub: true,
            },
        },
        {
            id: 2,
            children: [
                {
                    children: [],
                    data: {
                        id: 4,
                        title: 'Page 2.1',
                        hasSub: true,
                    },
                },
            ],
            data: {
                id: 2,
                title: 'Page 2',
                hasSub: false,
            },
        },
    ];

    const columnListAdapter = render(
        <ColumnListAdapter
            active={3}
            data={data}
            loading={false}
            onPageChange={jest.fn()}
            page={undefined}
            pageCount={0}
            schema={{}}
            selections={[]}
        />
    );

    expect(columnListAdapter).toMatchSnapshot();
});

test('Execute onItemActivation callback when an item is clicked with the correct parameter', () => {
    const onItemActivationSpy = jest.fn();

    const data = [
        {
            id: 1,
            children: [
                {
                    children: [],
                    data: {
                        id: 3,
                        title: 'Page 1.1',
                        hasSub: false,
                    },
                },
            ],
            data: {
                id: 1,
                title: 'Page 1',
                hasSub: true,
            },
        },
        {
            id: 2,
            children: [
                {
                    children: [],
                    data: {
                        id: 4,
                        title: 'Page 2.1',
                        hasSub: true,
                    },
                },
            ],
            data: {
                id: 2,
                title: 'Page 2',
                hasSub: false,
            },
        },
    ];

    const columnListAdapter = mount(
        <ColumnListAdapter
            active={3}
            data={data}
            loading={false}
            onItemActivation={onItemActivationSpy}
            onPageChange={jest.fn()}
            page={undefined}
            pageCount={0}
            schema={{}}
            selections={[]}
        />
    );

    columnListAdapter.find('Item').at(1).simulate('click');

    expect(onItemActivationSpy).toBeCalledWith(2);
});
