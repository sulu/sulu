// @flow
import React from 'react';
import {mount, render} from 'enzyme';
import ResourceStore from '../../../../stores/ResourceStore';
import ResourceRequester from '../../../../services/ResourceRequester';
import ChangelogLine from '../../fields/ChangelogLine';
import FormInspector from '../../FormInspector';
import FormStore from '../../stores/FormStore';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import {translate} from '../../../../utils/Translator';

jest.mock('../../../../stores/ResourceStore', () => jest.fn());
jest.mock('../../stores/FormStore', () => jest.fn());
jest.mock('../../FormInspector', () => jest.fn(function() {
    this.getValueByPath = jest.fn();
}));

jest.mock('../../../../services/ResourceRequester', () => ({}));

jest.mock('../../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

beforeEach(() => {
    // $FlowFixMe
    ResourceRequester.get = jest.fn();
});

test('Render loader if changer and creator are not loaded yet', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));

    expect(render(<ChangelogLine {...fieldTypeDefaultProps} formInspector={formInspector} />)).toMatchSnapshot();
});

test('Render with loaded changer and creator', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));

    formInspector.getValueByPath.mockImplementation((path) => {
        switch (path) {
            case '/creator':
                return 1;
            case '/changer':
                return 2;
            case '/created':
                return '2018-09-27T08:22:00';
            case '/changed':
                return '2018-10-04T10:57:00';
        }
    });

    const creatorPromise = Promise.resolve({
        fullName: 'Max Mustermann',
    });

    const changerPromise = Promise.resolve({
        fullName: 'Erika Mustermann',
    });

    ResourceRequester.get.mockImplementation((resourceKey, id) => {
        switch (id) {
            case 1:
                return creatorPromise;
            case 2:
                return changerPromise;
        }
    });

    const changelogLine = mount(<ChangelogLine {...fieldTypeDefaultProps} formInspector={formInspector} />);

    expect(ResourceRequester.get).toHaveBeenCalledTimes(2);
    expect(ResourceRequester.get).toBeCalledWith('users', 1);
    expect(ResourceRequester.get).toBeCalledWith('users', 2);

    return Promise.all([creatorPromise, changerPromise]).then(() => {
        changelogLine.update();
        expect(changelogLine.find('p')).toHaveLength(2);
        expect(changelogLine.find('p').at(0).text()).toEqual('sulu_admin.changelog_line_changer');
        expect(changelogLine.find('p').at(1).text()).toEqual('sulu_admin.changelog_line_creator');

        expect(translate).toBeCalledWith(
            'sulu_admin.changelog_line_creator',
            {created: '9/27/2018, 8:22:00 AM', creator: 'Max Mustermann'}
        );
        expect(translate).toBeCalledWith(
            'sulu_admin.changelog_line_changer',
            {changed: '10/4/2018, 10:57:00 AM', changer: 'Erika Mustermann'}
        );
    });
});

test('Render with no changer and creator', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));

    formInspector.getValueByPath.mockImplementation((path) => {
        switch (path) {
            case '/created':
                return '2018-09-27T08:22:00';
            case '/changed':
                return '2018-10-04T10:57:00';
        }
    });

    const changelogLine = mount(<ChangelogLine {...fieldTypeDefaultProps} formInspector={formInspector} />);

    expect(ResourceRequester.get).not.toBeCalled();

    expect(changelogLine.find('p')).toHaveLength(2);
    expect(changelogLine.find('p').at(0).text()).toEqual('sulu_admin.changelog_line_changer');
    expect(changelogLine.find('p').at(1).text()).toEqual('sulu_admin.changelog_line_creator');

    expect(translate).toBeCalledWith(
        'sulu_admin.changelog_line_creator',
        {created: '9/27/2018, 8:22:00 AM', creator: 'undefined'}
    );
    expect(translate).toBeCalledWith(
        'sulu_admin.changelog_line_changer',
        {changed: '10/4/2018, 10:57:00 AM', changer: 'undefined'}
    );
});
