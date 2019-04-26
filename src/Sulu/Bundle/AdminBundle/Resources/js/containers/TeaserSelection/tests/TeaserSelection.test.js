// @flow
import React from 'react';
import {mount} from 'enzyme';
import TeaserSelection from '../TeaserSelection';
import Item from '../Item';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

test('Render teaser selection with data', () => {
    const value = {
        displayOption: '',
        items: [
            {
                description: 'Description',
                id: 2,
                title: 'Title',
                type: 'pages',
            },
        ],
    };

    const teaserSelection = mount(<TeaserSelection onChange={jest.fn()} value={value} />);

    expect(teaserSelection.render()).toMatchSnapshot();
});

test('Open and close items when clicking on the pen icon', () => {
    const value = {
        displayOption: '',
        items: [
            {
                description: 'Description',
                id: 2,
                title: 'Title',
                type: 'pages',
            },
            {
                description: 'Description 2',
                id: 6,
                title: 'Title 2',
                type: 'pages',
            },
        ],
    };

    const teaserSelection = mount(<TeaserSelection onChange={jest.fn()} value={value} />);

    expect(teaserSelection.find(Item).at(0).prop('editing')).toEqual(false);
    expect(teaserSelection.find(Item).at(1).prop('editing')).toEqual(false);

    teaserSelection.find('Icon[name="su-pen"]').at(0).parent().prop('onClick')();
    teaserSelection.update();

    expect(teaserSelection.find(Item).at(0).prop('editing')).toEqual(true);
    expect(teaserSelection.find(Item).at(1).prop('editing')).toEqual(false);

    teaserSelection.find('Icon[name="su-pen"]').at(0).parent().prop('onClick')();
    teaserSelection.update();

    expect(teaserSelection.find(Item).at(0).prop('editing')).toEqual(true);
    expect(teaserSelection.find(Item).at(1).prop('editing')).toEqual(true);

    teaserSelection.find('Button[children="sulu_admin.cancel"]').at(0).prop('onClick')();
    teaserSelection.update();

    expect(teaserSelection.find(Item).at(0).prop('editing')).toEqual(false);
    expect(teaserSelection.find(Item).at(1).prop('editing')).toEqual(true);
});

test('Call onChange with new values when apply button is clicked', () => {
    const changeSpy = jest.fn();

    const value = {
        displayOption: '',
        items: [
            {
                description: 'Description',
                id: 2,
                title: 'Title',
                type: 'pages',
            },
            {
                description: 'Description 2',
                id: 6,
                title: 'Title 2',
                type: 'pages',
            },
        ],
    };

    const teaserSelection = mount(<TeaserSelection onChange={changeSpy} value={value} />);

    teaserSelection.find('Icon[name="su-pen"]').at(1).parent().prop('onClick')();
    teaserSelection.update();

    teaserSelection.find('Input').prop('onChange')('Edited Title 2');
    teaserSelection.find('TextArea').prop('onChange')('Edited Description 2');

    teaserSelection.find('Button[children="sulu_admin.apply"]').prop('onClick')();

    expect(changeSpy).toBeCalledWith(
        {
            displayOption: '',
            items: [
                {
                    description: 'Description',
                    id: 2,
                    title: 'Title',
                    type: 'pages',
                },
                {
                    description: 'Edited Description 2',
                    id: 6,
                    title: 'Edited Title 2',
                    type: 'pages',
                },
            ],
        }
    );
});

test('Call onChange with new values after one item is removed', () => {
    const changeSpy = jest.fn();

    const value = {
        displayOption: '',
        items: [
            {
                description: 'Description',
                id: 2,
                title: 'Title',
                type: 'pages',
            },
            {
                description: 'Description 2',
                id: 6,
                title: 'Title 2',
                type: 'pages',
            },
        ],
    };

    const teaserSelection = mount(<TeaserSelection onChange={changeSpy} value={value} />);

    teaserSelection.find('Icon[name="su-trash-alt"]').at(0).parent().prop('onClick')();

    expect(changeSpy).toBeCalledWith(
        {
            displayOption: '',
            items: [
                {
                    description: 'Description 2',
                    id: 6,
                    title: 'Title 2',
                    type: 'pages',
                },
            ],
        }
    );
});

test('Call onChange with new values after items are sorted', () => {
    const changeSpy = jest.fn();

    const value = {
        displayOption: '',
        items: [
            {
                description: 'Description',
                id: 2,
                title: 'Title',
                type: 'pages',
            },
            {
                description: 'Description 2',
                id: 6,
                title: 'Title 2',
                type: 'pages',
            },
            {
                description: 'Description 3',
                id: 9,
                title: 'Title 3',
                type: 'pages',
            },
        ],
    };

    const teaserSelection = mount(<TeaserSelection onChange={changeSpy} value={value} />);

    teaserSelection.find('MultiItemSelection').prop('onItemsSorted')(2, 1);

    expect(changeSpy).toBeCalledWith(
        {
            displayOption: '',
            items: [
                {
                    description: 'Description',
                    id: 2,
                    title: 'Title',
                    type: 'pages',
                },
                {
                    description: 'Description 3',
                    id: 9,
                    title: 'Title 3',
                    type: 'pages',
                },
                {
                    description: 'Description 2',
                    id: 6,
                    title: 'Title 2',
                    type: 'pages',
                },
            ],
        }
    );
});
