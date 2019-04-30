// @flow
import React from 'react';
import {observable} from 'mobx';
import {mount} from 'enzyme';
import TextEditor from '../../TextEditor';
import TeaserSelection from '../TeaserSelection';
import TeaserStore from '../stores/TeaserStore';
import Item from '../Item';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../TextEditor', () => jest.fn(({value}) => (<textarea onChange={jest.fn()} value={value} />)));

jest.mock('../stores/TeaserStore', () => jest.fn());

test('Render loading teaser selection', () => {
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

    // $FlowFixMe
    TeaserStore.mockImplementation(function() {
        this.add = jest.fn();
        this.findById = jest.fn();
        this.loading = true;
    });

    const teaserSelection = mount(<TeaserSelection locale={observable.box('en')} onChange={jest.fn()} value={value} />);

    teaserSelection.update();
    expect(teaserSelection.render()).toMatchSnapshot();
});

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

    // $FlowFixMe
    TeaserStore.mockImplementation(function() {
        this.add = jest.fn();
        this.findById = jest.fn();
    });

    const teaserSelection = mount(<TeaserSelection locale={observable.box('en')} onChange={jest.fn()} value={value} />);
    teaserSelection.instance().teaserStore.loading = false;

    teaserSelection.update();
    expect(teaserSelection.render()).toMatchSnapshot();
});

test('Add passed data to TeaserStore', () => {
    const value = {
        displayOption: '',
        items: [
            {
                description: 'Description 1',
                id: 2,
                title: 'Title 1',
                type: 'pages',
            },
            {
                description: 'Description 2',
                id: 3,
                title: 'Title 2',
                type: 'contacts',
            },
        ],
    };

    // $FlowFixMe
    TeaserStore.mockImplementation(function() {
        this.add = jest.fn();
        this.findById = jest.fn();
    });

    const teaserSelection = mount(<TeaserSelection locale={observable.box('en')} onChange={jest.fn()} value={value} />);

    expect(teaserSelection.instance().teaserStore.add).toBeCalledTimes(2);
    expect(teaserSelection.instance().teaserStore.add).toBeCalledWith('pages', 2);
    expect(teaserSelection.instance().teaserStore.add).toBeCalledWith('contacts', 3);
});

test('Load combined data from TeaserStore and props', () => {
    const value = {
        displayOption: '',
        items: [
            {
                description: 'Edited Page Description',
                id: 2,
                title: 'Edited Page Title',
                type: 'pages',
            },
            {
                description: undefined,
                id: 3,
                title: undefined,
                type: 'contacts',
            },
            {
                id: 4,
                type: 'contacts',
            },
        ],
    };

    // $FlowFixMe
    TeaserStore.mockImplementation(function() {
        this.add = jest.fn();

        this.findById = jest.fn((type, id) => {
            if (type === 'pages' && id === 2) {
                return {
                    description: 'Page Description',
                    id: 2,
                    title: 'Page',
                    type: 'pages',
                };
            }

            if (type === 'contacts' && id === 3) {
                return {
                    description: 'Contact Description 1',
                    id: 3,
                    title: 'Contact 1',
                    type: 'contacts',
                };
            }

            if (type === 'contacts' && id === 4) {
                return {
                    description: 'Contact Description 2',
                    id: 4,
                    title: 'Contact 2',
                    type: 'contacts',
                };
            }

            throw new Error('This case should not happen!');
        });
    });

    const teaserSelection = mount(<TeaserSelection locale={observable.box('en')} onChange={jest.fn()} value={value} />);

    teaserSelection.update();
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

    // $FlowFixMe
    TeaserStore.mockImplementation(function() {
        this.add = jest.fn();
        this.findById = jest.fn();
    });

    const teaserSelection = mount(<TeaserSelection locale={observable.box('en')} onChange={jest.fn()} value={value} />);

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

    // $FlowFixMe
    TeaserStore.mockImplementation(function() {
        this.add = jest.fn();
        this.findById = jest.fn();
    });

    const teaserSelection = mount(<TeaserSelection locale={observable.box('en')} onChange={changeSpy} value={value} />);

    teaserSelection.find('Icon[name="su-pen"]').at(1).parent().prop('onClick')();
    teaserSelection.update();

    teaserSelection.find('Input').prop('onChange')('Edited Title 2');
    teaserSelection.find(TextEditor).prop('onChange')('Edited Description 2');

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

    // $FlowFixMe
    TeaserStore.mockImplementation(function() {
        this.add = jest.fn();
        this.findById = jest.fn();
    });

    const teaserSelection = mount(<TeaserSelection locale={observable.box('en')} onChange={changeSpy} value={value} />);

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

    // $FlowFixMe
    TeaserStore.mockImplementation(function() {
        this.add = jest.fn();
        this.findById = jest.fn();
    });

    const teaserSelection = mount(<TeaserSelection locale={observable.box('en')} onChange={changeSpy} value={value} />);

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
