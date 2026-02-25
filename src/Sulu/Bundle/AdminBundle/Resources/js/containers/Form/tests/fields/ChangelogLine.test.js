// @flow
import React from 'react';
import {render, screen, waitFor} from '@testing-library/react';
import ResourceStore from '../../../../stores/ResourceStore';
import ResourceRequester from '../../../../services/ResourceRequester';
import ChangelogLine from '../../fields/ChangelogLine';
import FormInspector from '../../FormInspector';
import ResourceFormStore from '../../stores/ResourceFormStore';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import {translate} from '../../../../utils/Translator';

jest.mock('../../../../stores/ResourceStore', () => jest.fn());
jest.mock('../../stores/ResourceFormStore', () => jest.fn());
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
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    formInspector.getValueByPath.mockImplementation((path) => {
        switch (path) {
            case '/creator':
                return 1;
            case '/changer':
                return 2;
        }
    });
    ResourceRequester.get.mockImplementation(() => new Promise(() => {}));

    const {asFragment} = render(<ChangelogLine {...fieldTypeDefaultProps} formInspector={formInspector} />);
    expect(asFragment()).toMatchSnapshot();
});

test('Render with loaded changer and creator', async() => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

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

    ResourceRequester.get.mockImplementation((resourceKey, {id}) => {
        switch (id) {
            case 1:
                return creatorPromise;
            case 2:
                return changerPromise;
        }
    });

    render(<ChangelogLine {...fieldTypeDefaultProps} formInspector={formInspector} />);

    expect(ResourceRequester.get).toHaveBeenCalledTimes(2);
    expect(ResourceRequester.get).toBeCalledWith('users', {id: 1});
    expect(ResourceRequester.get).toBeCalledWith('users', {id: 2});

    await waitFor(() => {
        expect(screen.getAllByText(/sulu_admin\.changelog_line_/)).toHaveLength(2);
    });
    expect(screen.getByText('sulu_admin.changelog_line_changer'))
        .toHaveTextContent('sulu_admin.changelog_line_changer');
    expect(screen.getByText('sulu_admin.changelog_line_creator'))
        .toHaveTextContent('sulu_admin.changelog_line_creator');

    expect(translate).toBeCalledWith(
        'sulu_admin.changelog_line_creator',
        {created: '9/27/2018, 8:22:00 AM', creator: 'Max Mustermann'}
    );
    expect(translate).toBeCalledWith(
        'sulu_admin.changelog_line_changer',
        {changed: '10/4/2018, 10:57:00 AM', changer: 'Erika Mustermann'}
    );
});

test('Render with no changer and creator', async() => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    formInspector.getValueByPath.mockImplementation((path) => {
        switch (path) {
            case '/created':
                return '2018-09-27T08:22:00';
            case '/changed':
                return '2018-10-04T10:57:00';
        }
    });

    render(<ChangelogLine {...fieldTypeDefaultProps} formInspector={formInspector} />);

    expect(ResourceRequester.get).not.toBeCalled();

    await waitFor(() => {
        expect(screen.getAllByText(/sulu_admin\.changelog_line_/)).toHaveLength(2);
    });
    expect(screen.getByText('sulu_admin.changelog_line_changer'))
        .toHaveTextContent('sulu_admin.changelog_line_changer');
    expect(screen.getByText('sulu_admin.changelog_line_creator'))
        .toHaveTextContent('sulu_admin.changelog_line_creator');

    expect(translate).toBeCalledWith(
        'sulu_admin.changelog_line_creator',
        {created: '9/27/2018, 8:22:00 AM', creator: 'undefined'}
    );
    expect(translate).toBeCalledWith(
        'sulu_admin.changelog_line_changer',
        {changed: '10/4/2018, 10:57:00 AM', changer: 'undefined'}
    );
});

test('Render with deleted changer and existing creator', async() => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

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

    const creatorPromise = Promise.reject({
        status: 404,
    });

    const changerPromise = Promise.resolve({
        fullName: 'Erika Mustermann',
    });

    ResourceRequester.get.mockImplementation((resourceKey, {id}) => {
        switch (id) {
            case 1:
                return creatorPromise;
            case 2:
                return changerPromise;
        }
    });

    render(<ChangelogLine {...fieldTypeDefaultProps} formInspector={formInspector} />);

    expect(ResourceRequester.get).toHaveBeenCalledTimes(2);
    expect(ResourceRequester.get).toBeCalledWith('users', {id: 1});
    expect(ResourceRequester.get).toBeCalledWith('users', {id: 2});

    await waitFor(() => {
        expect(screen.getAllByText(/sulu_admin\.changelog_line_/)).toHaveLength(2);
    });
    expect(screen.getByText('sulu_admin.changelog_line_changer'))
        .toHaveTextContent('sulu_admin.changelog_line_changer');
    expect(screen.getByText('sulu_admin.changelog_line_creator'))
        .toHaveTextContent('sulu_admin.changelog_line_creator');

    expect(translate).toBeCalledWith(
        'sulu_admin.changelog_line_creator',
        {created: '9/27/2018, 8:22:00 AM', creator: 'undefined'}
    );
    expect(translate).toBeCalledWith(
        'sulu_admin.changelog_line_changer',
        {changed: '10/4/2018, 10:57:00 AM', changer: 'Erika Mustermann'}
    );
});

test('Render with existing changer and deleted creator', async() => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

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

    const changerPromise = Promise.reject({
        status: 404,
    });

    ResourceRequester.get.mockImplementation((resourceKey, {id}) => {
        switch (id) {
            case 1:
                return creatorPromise;
            case 2:
                return changerPromise;
        }
    });

    render(<ChangelogLine {...fieldTypeDefaultProps} formInspector={formInspector} />);

    expect(ResourceRequester.get).toHaveBeenCalledTimes(2);
    expect(ResourceRequester.get).toBeCalledWith('users', {id: 1});
    expect(ResourceRequester.get).toBeCalledWith('users', {id: 2});

    await waitFor(() => {
        expect(screen.getAllByText(/sulu_admin\.changelog_line_/)).toHaveLength(2);
    });
    expect(screen.getByText('sulu_admin.changelog_line_changer'))
        .toHaveTextContent('sulu_admin.changelog_line_changer');
    expect(screen.getByText('sulu_admin.changelog_line_creator'))
        .toHaveTextContent('sulu_admin.changelog_line_creator');

    expect(translate).toBeCalledWith(
        'sulu_admin.changelog_line_creator',
        {created: '9/27/2018, 8:22:00 AM', creator: 'Max Mustermann'}
    );
    expect(translate).toBeCalledWith(
        'sulu_admin.changelog_line_changer',
        {changed: '10/4/2018, 10:57:00 AM', changer: 'undefined'}
    );
});
