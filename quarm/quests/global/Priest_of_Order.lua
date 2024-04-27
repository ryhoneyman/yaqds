function event_say(e)
	local is_special_flag_response = false;
	if(e.message:findi("Hail")) then
		e.self:Say("Greetings, " .. e.other:GetCleanName() .. " .  Are you a child of Order? Then you've come to the right place. Erollisi Marr has blessed the Priests of Order with the ability to ordain adventurers in a union of souls. Are you interested in such a [union]?");
	elseif(e.message:findi("union")) then
		e.self:Say("Yes. Thanks to the blessing of Erollisi during Her holy day, we are able to temporarily bless unions of marriage. This ceremony is free of charge; our compensation is to spread Her word. To begin, please state the name of your family. You may also [reset] your family name, provided you are not already married.");
	elseif(e.message:findi("reset") and e.other:IsMarried() == false) then
		e.self:Say("Very well. Your surname has been reset.");
		e.other:SetTemporaryLastName("");
	else
		if(e.other:HasTemporaryLastName() == true and e.other:IsMarried() == false) then
			e.other:SetMarried(e.message);
		elseif(e.other:IsMarried() == false) then
			e.other:SetTemporaryLastName(e.message);
		elseif(e.other:IsMarried() == true) then
			e.self:Say("You're already married. I hope your love is eternal!");
		end
	end
end