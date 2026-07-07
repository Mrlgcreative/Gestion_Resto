const { contextBridge, ipcRenderer } = require('electron')

contextBridge.exposeInMainWorld('electronAPI', {
    getAppInfo: () => ipcRenderer.invoke('get-app-info'),
    getScreenSize: () => ipcRenderer.invoke('get-screen-size'),
    onMenuAction: (callback) => ipcRenderer.on('menu-action', (_event, action) => callback(action)),
})
