// Copyright (C) 2012 
// Author Wei-Jen Chen 2012

#include "stdafx.h"
#include "Logger.h"

#include <windows.h>
#include <stdlib.h>
#include <stdio.h>
#include <ctime>
#include "WinBase.h"

Logger* Logger::m_sInstance = NULL;

Logger::Logger()
{
	// TODO user do not have the right to write here !!!
	m_szLogFile = L"C:\\VirtSys.log";
	m_bIsLogging = false;
}
Logger::~Logger()
{
}

Logger& Logger::getSingleton()
{
	if(m_sInstance == NULL) 
	{
		m_sInstance = new Logger();
	}
	return (*m_sInstance);
}

Logger* Logger::getSingletonPtr()
{
	if(m_sInstance == NULL) 
	{
		m_sInstance = new Logger();
	}
	return m_sInstance;
}

void Logger::setLogFile(std::wstring szLogFile)
{
	m_szLogFile = szLogFile;
}

void Logger::enable(bool bEnable)
{
	m_bEnable = bEnable;
}

void Logger::debug(const wchar_t * format,...)
{
	#define MAX_DBG_MSG_LEN (4096)
    wchar_t buf[MAX_DBG_MSG_LEN];
	wchar_t msg[MAX_DBG_MSG_LEN];
	
	wchar_t modname[200];
	GetModuleFileName(NULL, modname, sizeof(modname));
	
	va_list args;
    va_start(args, format);
	vswprintf_s(buf, format, args);
    va_end(args);

	msg[0] = '\0';
	lstrcat(msg, modname);
	lstrcat(msg, L": ");
	lstrcat(msg, buf);

    OutputDebugString(msg);
}

void Logger::log(char *fmt,...)
{
	if(!m_bEnable)
		return;

	m_bIsLogging = true;

	va_list args;

	char temp[5000];
	HANDLE hFile;
	
	if((hFile = CreateFileW(m_szLogFile.c_str(), GENERIC_WRITE, 0, NULL, OPEN_ALWAYS, FILE_ATTRIBUTE_NORMAL, NULL)) <0)
	{
		this->debug(L"Failed to open log file %s", m_szLogFile.c_str());
		return;
	}
	
	_llseek((HFILE)hFile, 0, SEEK_END);

	
	DWORD dw;

	time_t* rawtime = new time_t;
	struct tm * timeinfo;
	time(rawtime);
	timeinfo = localtime(rawtime);
	wsprintfA(temp, "[%d/%02d/%02d %02d:%02d:%02d] ", 
		timeinfo->tm_year + 1900, 
		timeinfo->tm_mon + 1, 
		timeinfo->tm_mday,
		timeinfo->tm_hour, 
		timeinfo->tm_min, 
		timeinfo->tm_sec);
	WriteFile(hFile, temp, strlen(temp), &dw, NULL);
	
	char modname[200];
	GetModuleFileNameA(NULL, modname, sizeof(modname));
	wsprintfA(temp, "%s : ", modname);
	WriteFile(hFile, temp, strlen(temp), &dw, NULL);
	
	va_start(args,fmt);
	vsprintf_s(temp, fmt, args);
	va_end(args);
	WriteFile(hFile, temp, strlen(temp), &dw, NULL);

	wsprintfA(temp, "\r\n");
	WriteFile(hFile, temp, strlen(temp), &dw, NULL);

	_lclose((HFILE)hFile);
	
	m_bIsLogging = false;
}