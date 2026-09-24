const { app, BrowserWindow, ipcMain, screen } = require('electron')
const path = require('path')
const { spawn } = require('child_process')

const isDev = !app.isPackaged

let mainWindow = null
let phpProcess = null
let viteProcess = null

function getPhpBinary() {
    if (isDev) return 'php'
    const ext = process.platform === 'win32' ? '.exe' : ''
    return path.join(process.resourcesPath, 'php', `php${ext}`)
}

function getBackendPath() {
    return isDev
        ? path.join(__dirname, '..', 'backend')
        : path.join(process.resourcesPath, 'backend')
}

function startPhpServer() {
    const phpBin = getPhpBinary()
    const backendPath = getBackendPath()

    phpProcess = spawn(phpBin, [
        'artisan', 'serve',
        '--host=127.0.0.1', '--port=8080',
    ], {
        cwd: backendPath,
        stdio: ['pipe', 'pipe', 'pipe'],
    })

    phpProcess.stdout.on('data', (d) => console.log(`[PHP] ${d}`))
    phpProcess.stderr.on('data', (d) => console.error(`[PHP] ${d}`))
    phpProcess.on('close', (c) => console.log(`[PHP] exited with ${c}`))
}

function startViteDev() {
    if (!isDev) return
    const backendPath = getBackendPath()

    viteProcess = spawn('npx', ['vite'], {
        cwd: backendPath,
        stdio: ['pipe', 'pipe', 'pipe'],
        shell: true,
    })

    viteProcess.stdout.on('data', (d) => console.log(`[Vite] ${d}`))
    viteProcess.stderr.on('data', (d) => console.error(`[Vite] ${d}`))
}

function createWindow() {
    const { width, height } = screen.getPrimaryDisplay().workAreaSize

    mainWindow = new BrowserWindow({
        width: Math.min(width, 1400),
        height: Math.min(height, 900),
        minWidth: 1024,
        minHeight: 768,
        webPreferences: {
            preload: path.join(__dirname, 'preload.js'),
            contextIsolation: true,
            nodeIntegration: false,
        },
        show: false,
    })

    mainWindow.loadURL('http://127.0.0.1:8080')
    mainWindow.once('ready-to-show', () => mainWindow.show())

    if (isDev) mainWindow.webContents.openDevTools()
}

app.whenReady().then(() => {
    startPhpServer()
    if (isDev) startViteDev()
    setTimeout(createWindow, 2000) // wait for servers to start
})

app.on('window-all-closed', () => {
    if (phpProcess) phpProcess.kill()
    if (viteProcess) viteProcess.kill()
    if (process.platform !== 'darwin') app.quit()
})

app.on('before-quit', () => {
    if (phpProcess) phpProcess.kill()
    if (viteProcess) viteProcess.kill()
})

ipcMain.handle('get-app-info', () => ({
    version: app.getVersion(),
    platform: process.platform,
    backendUrl: 'http://127.0.0.1:8080',
}))

ipcMain.handle('get-screen-size', () => {
    const { width, height } = screen.getPrimaryDisplay().workAreaSize
    return { width, height }
})
