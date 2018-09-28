// @flow
import {render, mount} from 'enzyme';
import React from 'react';
import Matrix from '../Matrix';
import Row from '../Row';
import Item from '../Item';

afterEach(() => {
    if (document.body) {
        document.body.innerHTML = '';
    }
});

jest.mock('../../../utils/Translator', () => ({
    translate: function(key) {
        switch (key) {
            case 'sulu_admin.activate_all':
                return 'Activate all';
            case 'sulu_admin.deactivate_all':
                return 'Deactivate all';
        }
    },
}));

test('Render the Matrix component', () => {
    const handleChange = jest.fn();

    expect(render(
        <Matrix onChange={handleChange} title="Global">
            <Row name="global.articles" title="articles">
                <Item icon="su-pen" name="view" />
                <Item icon="su-plus" name="edit" />
                <Item icon="su-trash-alt" name="delete" />
            </Row>
            <Row name="global.redirects" title="redirects">
                <Item icon="su-pen" name="view" />
            </Row>
            <Row name="global.settings" title="settings">
                <Item icon="su-pen" name="view" />
                <Item icon="su-plus" name="edit" />
            </Row>
        </Matrix>
    )).toMatchSnapshot();
});

test('Render the Matrix component with values', () => {
    const handleChange = jest.fn();
    const values = {
        'global.articles': {
            'view': true,
            'edit': true,
            'delete': false,
        },
        'global.redirects': {
            'view': true,
        },
        'global.settings': {
            'view': true,
            'edit': false,
        },
    };

    expect(render(
        <Matrix onChange={handleChange} title="Global" values={values}>
            <Row name="global.articles" title="articles">
                <Item icon="su-pen" name="view" />
                <Item icon="su-plus" name="edit" />
                <Item icon="su-trash-alt" name="delete" />
            </Row>
            <Row name="global.redirects" title="redirects">
                <Item icon="su-pen" name="view" />
            </Row>
            <Row name="global.settings" title="settings">
                <Item icon="su-pen" name="view" />
                <Item icon="su-plus" name="edit" />
            </Row>
        </Matrix>
    )).toMatchSnapshot();
});

test('Changing a value should call onChange ', () => {
    const handleChange = jest.fn();
    const values = {
        'global.articles': {
            'view': true,
            'edit': true,
            'delete': false,
        },
        'global.redirects': {
            'view': true,
        },
        'global.settings': {
            'view': true,
            'edit': false,
        },
    };

    const matrix = mount(
        <Matrix onChange={handleChange} title="Global" values={values}>
            <Row name="global.articles" title="articles">
                <Item icon="su-pen" name="view" />
                <Item icon="su-plus" name="edit" />
                <Item icon="su-trash-alt" name="delete" />
            </Row>
            <Row name="global.redirects" title="redirects">
                <Item icon="su-pen" name="view" />
            </Row>
            <Row name="global.settings" title="settings">
                <Item icon="su-pen" name="view" />
                <Item icon="su-plus" name="edit" />
            </Row>
        </Matrix>
    );

    const expectedValues = {
        'global.articles': {
            'view': true,
            'edit': true,
            'delete': false,
        },
        'global.redirects': {
            'view': false,
        },
        'global.settings': {
            'view': true,
            'edit': false,
        },
    };

    matrix.find(Item).at(3).simulate('click');
    expect(handleChange).toHaveBeenCalledWith(expectedValues);
});

test('Deactivate all button should call onChange', () => {
    const handleChange = jest.fn();
    const values = {
        'global.articles': {
            'view': true,
            'edit': true,
            'delete': false,
        },
        'global.redirects': {
            'view': true,
        },
        'global.settings': {
            'view': true,
            'edit': false,
        },
    };

    const matrix = mount(
        <Matrix onChange={handleChange} title="Global" values={values}>
            <Row name="global.articles" title="articles">
                <Item icon="su-pen" name="view" />
                <Item icon="su-plus" name="edit" />
                <Item icon="su-trash-alt" name="delete" />
            </Row>
            <Row name="global.redirects" title="redirects">
                <Item icon="su-pen" name="view" />
            </Row>
            <Row name="global.settings" title="settings">
                <Item icon="su-pen" name="view" />
                <Item icon="su-plus" name="edit" />
            </Row>
        </Matrix>
    );

    const expectedValues = {
        'global.articles': {
            'view': false,
            'edit': false,
            'delete': false,
        },
        'global.redirects': {
            'view': true,
        },
        'global.settings': {
            'view': true,
            'edit': false,
        },
    };

    matrix.find('.rowButton').at(0).simulate('click');
    expect(handleChange).toHaveBeenCalledWith(expectedValues);
});

test('Activate all button should call onChange', () => {
    const handleChange = jest.fn();
    const values = {
        'global.articles': {
            'view': false,
            'edit': false,
            'delete': false,
        },
        'global.redirects': {
            'view': true,
        },
        'global.settings': {
            'view': true,
            'edit': false,
        },
    };

    const matrix = mount(
        <Matrix onChange={handleChange} title="Global" values={values}>
            <Row name="global.articles" title="articles">
                <Item icon="su-pen" name="view" />
                <Item icon="su-plus" name="edit" />
                <Item icon="su-trash-alt" name="delete" />
            </Row>
            <Row name="global.redirects" title="redirects">
                <Item icon="su-pen" name="view" />
            </Row>
            <Row name="global.settings" title="settings">
                <Item icon="su-pen" name="view" />
                <Item icon="su-plus" name="edit" />
            </Row>
        </Matrix>
    );

    const expectedValues = {
        'global.articles': {
            'view': true,
            'edit': true,
            'delete': true,
        },
        'global.redirects': {
            'view': true,
        },
        'global.settings': {
            'view': true,
            'edit': false,
        },
    };

    matrix.find('.rowButton').at(0).simulate('click');
    expect(handleChange).toHaveBeenCalledWith(expectedValues);
});

test('Activate all button should call onChange with all values, even when the value does not exists', () => {
    const handleChange = jest.fn();
    const values = {
        'global.articles': {
            'view': false,
            'edit': false,
            'delete': false,
        },
        'global.redirects': {
            'view': true,
        },
    };

    const matrix = mount(
        <Matrix onChange={handleChange} title="Global" values={values}>
            <Row name="global.articles" title="articles">
                <Item icon="su-pen" name="view" />
                <Item icon="su-plus" name="edit" />
                <Item icon="su-trash-alt" name="delete" />
            </Row>
            <Row name="global.redirects" title="redirects">
                <Item icon="su-pen" name="view" />
            </Row>
            <Row name="global.settings" title="settings">
                <Item icon="su-pen" name="view" />
                <Item icon="su-plus" name="edit" />
            </Row>
        </Matrix>
    );

    const expectedValues = {
        'global.articles': {
            'view': false,
            'edit': false,
            'delete': false,
        },
        'global.redirects': {
            'view': true,
        },
        'global.settings': {
            'view': true,
            'edit': true,
        },
    };

    matrix.find('.rowButton').at(2).simulate('click');
    expect(handleChange).toHaveBeenCalledWith(expectedValues);
});
