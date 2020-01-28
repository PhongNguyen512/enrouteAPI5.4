# EnrouteAPI Setup

After you cloned this project, `cd` into the project

Install composer dependencies `composer install`

Create a copy of `.env.example` file, then rename it to `.env`

Setup the database path in `.env` file

Generate an app encryption key `php artisan key:generate`

# Run project

You have to `cd` to the project, then run a command `php artisan serve`

# Sample API URL
1. Check exist device `/api/deviceExist/[device_id]`
- GET method
- Return { `id`, `mac_addr` } or { `id`:`Id not exist` }

2. Create new device  `/api/newDevice`
- GET method
- Create a new empty record in database
- Return { `id`:`Newest ID in devices table` }

3. Authenticate `/api/authenticate`
- POST method
- Required: [device_id], [password]
- Return { `status`, `access_token` }

### All of API below require access_token
#### (Please add `Authorization`: `Bearer [access_token]` in header when sending a request
4. Update device info `/api/updateDeviceInfo`
- POST method
- Submit data through form
- Required: [device_id], [app_vesion], [mac_address], [ip_address], [total_device_space], [space_left]
- Note: `[space_left]` is for calculate `[disk_used]`
- Return { `status` }

5. Check disabled company `/api/checkCompanyDisabled/[company_id]`
- GET method
- Return { `message` }

6. Get franchise Link `/api/franchiseLink/[franchise_id]`
- GET method
- Return { `FranchiseLink` : [`{"title":"links"}`] , [ .. ] }

7. Get Sector File
- GET method
- With sector `/api/getSectorFile/595/[correct_lat,correct_lon]`
- Without sector `/api/getSectorFile/595/[0.0000001,0.0000001]`
- Return XML file
