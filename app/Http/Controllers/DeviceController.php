<?php

namespace App\Http\Controllers;

use App\Models\Device;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    /**
     * @return View
     */
    public function index(): View
    {
        $devices = Device::paginate(10);
        return view('devices.index', ['devices' => $devices]);
    }

    /**
     * @return View
     */
    public function create(): View
    {
        return view('devices.create');
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'device_name' => 'required|string|min:0',
            'share_code' => 'required|string|min:0',
        ]);

        Device::create($request->all());

        return redirect()->route('devices.index')->with('status', 'Device added successfully!');
    }

    /**
     * @param Device $device
     * @return View
     */
    public function edit(Device $device): View
    {
        return view('devices.edit', compact('device'));
    }

    /**
     * @param Request $request
     * @param Device $device
     * @return RedirectResponse
     */
    public function update(Request $request, Device $device): RedirectResponse
    {
        $request->validate([
            'device_name' => 'required|string|min:0',
            'share_code' => 'required|string|min:0',
        ]);

        $device->update($request->all());

        return redirect()->route('devices.index')->with('status', 'Device updated successfully');
    }

    /**
     * @param Device $device
     * @return RedirectResponse
     */
    public function destroy(Device $device): RedirectResponse
    {
        $device->delete();

        return redirect()->route('devices.index')->with('status', 'Device deleted successfully');
    }
}
