<?php namespace SammyK\LaravelFacebookSdk\Test;

use SammyK\LaravelFacebookSdk\SyncableGraphNodeTrait;

class FakeModel extends \Illuminate\Database\Eloquent\Model
{
    public static function firstByAttributes($data)
    {
        if( $data['facebook_id'] !== '1' ) {
            return null;
        }

        $obj = new static();
        $obj->faz = 'Baz';
        $obj->email = 'me@me.com';

        return $obj;
    }

    public function save(array $options = [])
    {
        return true;
    }
}

class MyEmptyModel extends FakeModel
{
    use SyncableGraphNodeTrait;

    protected static $graph_node_field_aliases = [];
}

class MyUserModel extends FakeModel
{
    use SyncableGraphNodeTrait;

    protected static $graph_node_field_aliases = [
        'id' => 'facebook_id',
        'foo' => 'faz',
        ];
}

class FacebookableTraitTest extends \PHPUnit_Framework_TestCase
{
    public function testFieldToColumnNameReturnsFieldNameWhenNoAliasPresent()
    {
        $my_empty_model = new MyEmptyModel();

        $column_name = $my_empty_model::fieldToColumnName('foo');

        $this->assertEquals('foo', $column_name);
    }

    public function testFieldToColumnNameReturnsAliasWhenAliasPresent()
    {
        $my_user_model = new MyUserModel();

        $column_name = $my_user_model::fieldToColumnName('foo');

        $this->assertEquals('faz', $column_name);
    }

    public function testCanGetDefaultFacebookObjectKeyName()
    {
        $my_empty_model = new MyEmptyModel();

        $facebook_object_key = $my_empty_model::getGraphNodeKeyName();

        $this->assertEquals('id', $facebook_object_key);
    }

    public function testCanGetCustomFacebookObjectKey()
    {
        $my_user_model = new MyUserModel();

        $facebook_object_key = $my_user_model::getGraphNodeKeyName();

        $this->assertEquals('facebook_id', $facebook_object_key);
    }

    public function testCanRemapFacebookFieldsToDatabaseArray()
    {
        $my_user_model = new MyUserModel();

        $my_object = new FakeModel();
        $my_user_model::mapGraphNodeFieldNamesToDatabaseColumnNames($my_object, [
                'id' => '1234567890',
                'foo' => 'My Foo',
                'email' => 'foo@bar.com',
            ]);

        $this->assertEquals($my_object->facebook_id, '1234567890');
        $this->assertEquals($my_object->faz, 'My Foo');
        $this->assertEquals($my_object->email, 'foo@bar.com');
    }

    public function testFirstOrNewFacebookObjectCreatesNewStaticObjectWhenOneDoesNotExist()
    {
        $my_user_model = new MyUserModel();
        $my_object = $my_user_model::firstOrNewGraphNode([
                'facebook_id' => '1234567890',
            ]);

        $this->assertNull($my_object->faz);
    }

    public function testFirstOrNewFacebookObjectReturnsExistingObject()
    {
        $my_user_model = new MyUserModel();
        $my_object = $my_user_model::firstOrNewGraphNode([
                'facebook_id' => '1',
            ]);

        $this->assertEquals('Baz', $my_object->faz);
    }

    public function testInsertsNewFacebookObjectDataIntoDatabase()
    {
        $my_user_model = new MyUserModel();

        $user_object = $my_user_model::createOrUpdateGraphNode([
                'id' => '1234567890',
                'foo' => 'My Foo',
                'email' => 'foo@bar.com',
            ]);

        $this->assertEquals($user_object->facebook_id, '1234567890');
        $this->assertEquals($user_object->faz, 'My Foo');
        $this->assertEquals($user_object->email, 'foo@bar.com');
    }

    public function testUpdatesExistingFacebookObjectDataInDatabase()
    {
        $my_user_model = new MyUserModel();

        $user_object = $my_user_model::createOrUpdateGraphNode([
                'id' => '1',
                'foo' => 'My Foo',
                'email' => 'foo@bar.com',
            ]);

        $this->assertEquals($user_object->facebook_id, '1');
        $this->assertEquals($user_object->faz, 'My Foo');
        $this->assertEquals($user_object->email, 'foo@bar.com');
    }
}